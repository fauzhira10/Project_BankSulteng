<?php

namespace App\Services;

use App\Models\BlokAudit;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Menjangkarkan hash blok jejak audit ke kontrak JangkarAudit di Ethereum dan
 * membandingkannya kembali.
 *
 * Pekerjaan Ethereum-nya sendiri (menandatangani transaksi, memanggil RPC)
 * dilakukan skrip Node blockchain/jangkar.mjs dengan pustaka ethers. PHP hanya
 * menjalankan skrip itu dan membaca satu baris JSON keluarannya.
 *
 * Yang dikirim ke Ethereum hanya nomor blok, hash blok, dan merkle root — tidak
 * pernah isi catatan audit ataupun data nasabah. Data di blockchain publik tidak
 * dapat dihapus, jadi data pribadi tidak boleh sampai ke sana.
 */
class JangkarEthereumService
{
    public function aktif(): bool
    {
        $eth = config('blockchain.ethereum');

        return (bool) $eth['aktif'] && filled($eth['rpc_url']) && filled($eth['alamat_kontrak']);
    }

    public function urlKontrak(): ?string
    {
        $alamat = config('blockchain.ethereum.alamat_kontrak');

        return filled($alamat) ? config('blockchain.ethereum.penjelajah').'/address/'.$alamat : null;
    }

    /**
     * Kirim satu blok ke Ethereum dan simpan hasilnya pada kolom jangkar_*.
     */
    public function jangkarkan(BlokAudit $blok): BlokAudit
    {
        $hasil = $this->jalankan(['kirim', (string) $blok->nomor, $blok->hash_blok, $blok->merkle_root]);

        if ($hasil['ok'] ?? false) {
            $blok->forceFill([
                'jangkar_status' => 'terkirim',
                'jangkar_jaringan' => $hasil['jaringan'] ?? null,
                'jangkar_tx' => $hasil['tx'] ?? $blok->jangkar_tx,
                'jangkar_blok_eth' => $hasil['blok_eth'] ?? $blok->jangkar_blok_eth,
                'jangkar_pada' => now(),
                'jangkar_galat' => null,
            ])->save();

            return $blok;
        }

        // 'konflik' = nomor blok ini sudah tercatat di Ethereum dengan hash lain.
        // Itu bukan kegagalan jaringan yang layak dicoba ulang, melainkan bukti
        // bahwa blok di server sudah berubah sejak dijangkarkan.
        $blok->forceFill([
            'jangkar_status' => ($hasil['status'] ?? null) === 'konflik' ? 'konflik' : 'gagal',
            'jangkar_jaringan' => $hasil['jaringan'] ?? $blok->jangkar_jaringan,
            'jangkar_galat' => mb_substr((string) ($hasil['galat'] ?? 'Galat tidak diketahui.'), 0, 1000),
        ])->save();

        return $blok;
    }

    /**
     * Jangkarkan blok-blok yang belum terkirim, urut dari yang tertua.
     *
     * Berhenti pada kegagalan pertama: bila jaringan terputus, mencoba blok
     * berikutnya hanya menambah waktu tunggu tanpa hasil.
     *
     * @return array{terkirim:int, gagal:int, konflik:bool, galat:?string}
     */
    public function jangkarkanTertunda(int $maks = 25): array
    {
        $terkirim = 0;

        $antrean = BlokAudit::query()
            ->where(fn ($q) => $q->whereNull('jangkar_status')->orWhere('jangkar_status', 'gagal'))
            ->orderBy('nomor')
            ->limit($maks)
            ->get();

        foreach ($antrean as $blok) {
            $this->jangkarkan($blok);

            if ($blok->jangkar_status !== 'terkirim') {
                return [
                    'terkirim' => $terkirim,
                    'gagal' => 1,
                    'konflik' => $blok->jangkar_status === 'konflik',
                    'galat' => "Blok #{$blok->nomor}: {$blok->jangkar_galat}",
                ];
            }

            $terkirim++;
        }

        return ['terkirim' => $terkirim, 'gagal' => 0, 'konflik' => false, 'galat' => null];
    }

    /**
     * Baca hash blok yang tercatat di Ethereum.
     *
     * @param  list<int>  $nomor
     * @return array<int, array{ada:bool, hash_blok:?string, merkle_root:?string, waktu:?int}>
     *
     * @throws RuntimeException bila skrip atau jaringan gagal
     */
    public function baca(array $nomor): array
    {
        if ($nomor === []) {
            return [];
        }

        $hasil = $this->jalankan(['baca', ...array_map('strval', $nomor)]);

        if (! ($hasil['ok'] ?? false)) {
            throw new RuntimeException('Gagal membaca Ethereum: '.($hasil['galat'] ?? 'galat tidak diketahui'));
        }

        $keluaran = [];
        foreach ($hasil['blok'] ?? [] as $n => $isi) {
            $keluaran[(int) $n] = $isi;
        }

        return $keluaran;
    }

    /**
     * Bandingkan blok di server dengan yang tercatat di Ethereum.
     *
     * Hasilnya diindeks nomor blok.
     *
     * @param  iterable<BlokAudit>  $daftarBlok
     * @return array<int, array{status:'cocok'|'berbeda'|'belum', hash_onchain:?string, waktu:?int}>
     */
    public function bandingkan(iterable $daftarBlok): array
    {
        $lokal = [];
        foreach ($daftarBlok as $blok) {
            $lokal[$blok->nomor] = $blok;
        }

        $onchain = $this->baca(array_keys($lokal));
        $hasil = [];

        foreach ($lokal as $n => $blok) {
            $catatan = $onchain[$n] ?? ['ada' => false, 'hash_blok' => null, 'waktu' => null];

            $hasil[$n] = [
                'status' => ! $catatan['ada']
                    ? 'belum'
                    : (hash_equals((string) $catatan['hash_blok'], (string) $blok->hash_blok) ? 'cocok' : 'berbeda'),
                'hash_onchain' => $catatan['hash_blok'],
                'waktu' => $catatan['waktu'],
            ];
        }

        return $hasil;
    }

    /**
     * @param  list<string>  $argumen
     * @return array<string, mixed>
     */
    protected function jalankan(array $argumen): array
    {
        $eth = config('blockchain.ethereum');

        $proses = Process::path(base_path('blockchain'))
            ->timeout(max(10, (int) $eth['batas_waktu']))
            ->env(array_filter([
                'ETH_RPC_URL' => $eth['rpc_url'],
                'ETH_ALAMAT_KONTRAK' => $eth['alamat_kontrak'],
                'ETH_BLOK_DEPLOY' => $eth['blok_deploy'] !== null ? (string) $eth['blok_deploy'] : null,
            ], fn ($v) => $v !== null && $v !== ''))
            ->run([$eth['node_bin'], 'jangkar.mjs', ...$argumen]);

        $baris = collect(preg_split('/\R/', trim($proses->output())))->filter()->last();
        $data = json_decode((string) $baris, true);

        if (! is_array($data)) {
            $galat = trim($proses->errorOutput()) ?: 'Keluaran skrip jangkar.mjs tidak dapat dibaca.';

            return ['ok' => false, 'galat' => mb_substr($galat, 0, 1000)];
        }

        return $data;
    }
}
