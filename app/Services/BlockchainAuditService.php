<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\BlokAudit;
use App\Support\MerkleTree;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Blockchain jejak audit: menambang catatan audit menjadi blok dan memeriksa
 * keutuhan seluruh rantai blok.
 *
 * Satu-satunya penulis tabel blok_audits. Tiap blok:
 *  - memuat sejumlah catatan audit (transaksi) yang disegel dengan merkle root,
 *  - menyegel hash blok sebelumnya, sehingga blok-blok membentuk rantai,
 *  - baru sah setelah ditemukan nonce yang membuat hash-nya diawali N angka nol
 *    (proof of work).
 *
 * Daun pohon Merkle adalah hash tiap catatan audit yang DIHITUNG ULANG dari
 * isinya (AuditTrail::hitungHash), bukan kolom hash_sekarang yang tersimpan.
 * Dengan begitu, pemalsu yang mengubah isi catatan lalu ikut memperbarui kolom
 * hash-nya tetap ketahuan di tingkat blok.
 */
class BlockchainAuditService
{
    /**
     * Tambang seluruh catatan audit yang belum masuk blok.
     *
     * @param  bool  $termasukSebagian  false = hanya bentuk blok yang sudah penuh
     *                                  (sisa catatan menunggu putaran berikutnya)
     * @return list<BlokAudit> blok baru, tanpa genesis
     *
     * @throws RuntimeException bila penambangan lain sedang berjalan atau ada
     *                          catatan audit yang sudah rusak sebelum ditambang
     */
    public function tambang(bool $termasukSebagian = true): array
    {
        // Dua penambang bersamaan akan berebut nomor blok yang sama. Indeks unik
        // pada nomor & hash_sebelumnya tetap menolak salah satunya, tetapi kunci
        // ini mencegah kerja proof of work yang sia-sia.
        $kunci = Cache::lock('blockchain:tambang', 600);

        if (! $kunci->get()) {
            throw new RuntimeException('Penambangan lain sedang berjalan. Coba lagi sebentar.');
        }

        try {
            $ukuran = max(1, (int) config('blockchain.ukuran_blok'));
            $terakhir = BlokAudit::query()->orderByDesc('nomor')->first() ?? $this->buatGenesis();
            $baru = [];

            while (true) {
                $calon = AuditTrail::query()
                    ->where('id', '>', $terakhir->audit_id_akhir)
                    ->orderBy('id')
                    ->limit($ukuran)
                    ->get();

                if ($calon->isEmpty() || ($calon->count() < $ukuran && ! $termasukSebagian)) {
                    break;
                }

                $this->pastikanCatatanUtuh($calon, $terakhir);

                $terakhir = $this->tambangBlok($terakhir, $calon);
                $baru[] = $terakhir;
            }

            return $baru;
        } finally {
            $kunci->release();
        }
    }

    /**
     * Blok ke-0. Tidak berisi catatan; hanya menjadi titik awal rantai.
     */
    protected function buatGenesis(): BlokAudit
    {
        return $this->simpanTertambang(new BlokAudit([
            'nomor' => 0,
            'hash_sebelumnya' => BlokAudit::HASH_NOL,
            'merkle_root' => MerkleTree::akar([]),
            'audit_id_awal' => null,
            'audit_id_akhir' => 0,
            'jumlah_transaksi' => 0,
            'daftar_transaksi' => [],
        ]));
    }

    /**
     * @param  Collection<int, AuditTrail>  $calon
     */
    protected function tambangBlok(BlokAudit $induk, Collection $calon): BlokAudit
    {
        $daftar = $calon
            ->map(fn (AuditTrail $b) => [(int) $b->id, $b->hitungHash($b->hash_sebelumnya)])
            ->values()
            ->all();

        return $this->simpanTertambang(new BlokAudit([
            'nomor' => $induk->nomor + 1,
            'hash_sebelumnya' => $induk->hash_blok,
            'merkle_root' => MerkleTree::akar(array_column($daftar, 1)),
            'audit_id_awal' => (int) $calon->first()->id,
            'audit_id_akhir' => (int) $calon->last()->id,
            'jumlah_transaksi' => count($daftar),
            'daftar_transaksi' => $daftar,
        ]));
    }

    /**
     * Proof of work: cari nonce terkecil yang membuat hash kepala blok diawali
     * N angka nol, lalu simpan bloknya.
     */
    protected function simpanTertambang(BlokAudit $blok): BlokAudit
    {
        $tingkat = (int) config('blockchain.tingkat_kesulitan');

        $blok->tingkat_kesulitan = $tingkat;
        // Detik bulat: kolom timestamp MySQL tidak menyimpan mikrodetik, sehingga
        // nilai yang dibaca ulang harus sama persis dengan yang ikut dihash.
        $blok->ditambang_pada = now()->startOfSecond();

        $kepala = $blok->kepala();
        $awalan = str_repeat('0', $tingkat);
        $mulai = hrtime(true);

        for ($nonce = 0; ; $nonce++) {
            $hash = BlokAudit::hashKepala($kepala, $nonce);

            if (str_starts_with($hash, $awalan)) {
                break;
            }
        }

        $blok->nonce = $nonce;
        $blok->hash_blok = $hash;
        $blok->durasi_tambang_ms = (int) round((hrtime(true) - $mulai) / 1e6);
        $blok->save();

        return $blok;
    }

    /**
     * Jangan menyegel catatan yang sudah rusak: blok yang dibangun di atas data
     * palsu akan terlihat sah. Rantai hash catatan-catatan calon harus menyambung
     * ke catatan terakhir di blok sebelumnya dan isinya masih sesuai hash-nya.
     *
     * @param  Collection<int, AuditTrail>  $calon
     */
    protected function pastikanCatatanUtuh(Collection $calon, BlokAudit $induk): void
    {
        $hashSebelumnya = AuditTrail::query()
            ->where('id', '<=', $induk->audit_id_akhir)
            ->orderByDesc('id')
            ->value('hash_sekarang');

        foreach ($calon as $baris) {
            if (($baris->hash_sebelumnya ?? null) !== $hashSebelumnya || ! $baris->hashCocok()) {
                throw new RuntimeException(
                    "Catatan audit #{$baris->id} sudah rusak sebelum ditambang, sehingga tidak disegel ke blok. "
                    .'Jalankan `php artisan audit:periksa` dan laporkan ke penanggung jawab sistem.'
                );
            }

            $hashSebelumnya = $baris->hash_sekarang;
        }
    }

    /**
     * Periksa seluruh rantai blok dari genesis.
     *
     * Yang diperiksa pada tiap blok:
     *  1. nomornya berurutan dan hash_sebelumnya sama dengan hash blok sebelumnya,
     *  2. hash kepala blok dihitung ulang masih sama (kepala tidak diubah),
     *  3. hash memenuhi proof of work,
     *  4. merkle root dari catatan audit SAAT INI sama dengan yang tersegel —
     *     bila tidak, sebutkan catatan mana yang diubah, dihapus, atau disisipkan.
     *
     * Pemeriksaan ini tidak menyentuh Ethereum. Pemalsu yang menambang ulang
     * seluruh blok sesudah titik pemalsuan akan lolos di sini; yang menangkapnya
     * adalah pembandingan dengan jangkar Ethereum (JangkarEthereumService).
     *
     * @return array{
     *     utuh: bool,
     *     jumlah_blok: int,
     *     transaksi_tercatat: int,
     *     transaksi_tertunda: int,
     *     blok_rusak: int,
     *     blok: array<int, array{nomor:int, valid:bool, masalah:list<string>}>
     * }
     */
    public function periksa(): array
    {
        $hasil = [];
        $sebelumnya = null;
        $tercatat = 0;

        foreach (BlokAudit::query()->orderBy('nomor')->cursor() as $blok) {
            $masalah = [];

            $nomorHarap = $sebelumnya ? $sebelumnya->nomor + 1 : 0;
            if ($blok->nomor !== $nomorHarap) {
                $masalah[] = "Nomor blok melompat: seharusnya #{$nomorHarap}. Ada blok yang dihapus.";
            }

            $induk = $sebelumnya?->hash_blok ?? BlokAudit::HASH_NOL;
            if ($blok->hash_sebelumnya !== $induk) {
                $masalah[] = $sebelumnya
                    ? "Tautan putus: hash_sebelumnya tidak sama dengan hash blok #{$sebelumnya->nomor}."
                    : 'Blok pertama tidak menunjuk hash nol (bukan genesis yang sah).';
            }

            if (! hash_equals((string) $blok->hash_blok, $blok->hitungHash())) {
                $masalah[] = 'Kepala blok diubah: hash yang dihitung ulang berbeda dengan hash tersimpan.';
            }

            if (! BlokAudit::memenuhiKesulitan((string) $blok->hash_blok, (int) $blok->tingkat_kesulitan)) {
                $masalah[] = "Hash blok tidak memenuhi proof of work (harus diawali {$blok->tingkat_kesulitan} angka nol).";
            }

            array_push($masalah, ...$this->periksaIsi($blok, $sebelumnya?->audit_id_akhir));

            $hasil[$blok->id] = [
                'nomor' => $blok->nomor,
                'valid' => $masalah === [],
                'masalah' => $masalah,
            ];

            $tercatat += $blok->jumlah_transaksi;
            $sebelumnya = $blok;
        }

        $tertunda = AuditTrail::query()
            ->where('id', '>', $sebelumnya?->audit_id_akhir ?? 0)
            ->count();

        $rusak = count(array_filter($hasil, fn ($h) => ! $h['valid']));

        return [
            'utuh' => $rusak === 0,
            'jumlah_blok' => count($hasil),
            'transaksi_tercatat' => $tercatat,
            'transaksi_tertunda' => $tertunda,
            'blok_rusak' => $rusak,
            'blok' => $hasil,
        ];
    }

    /**
     * Bandingkan catatan audit yang ada sekarang dengan yang tersegel di blok.
     *
     * @return list<string>
     */
    protected function periksaIsi(BlokAudit $blok, ?int $akhirSebelumnya): array
    {
        $tersimpan = [];
        foreach ($blok->daftar_transaksi ?? [] as [$id, $hash]) {
            $tersimpan[(int) $id] = (string) $hash;
        }

        $masalah = [];

        // Daftar salinan itu sendiri harus sesuai merkle root yang tersegel di
        // kepala blok; kalau tidak, rincian per catatan di bawah tidak bisa dipercaya.
        if (MerkleTree::akar(array_values($tersimpan)) !== $blok->merkle_root) {
            $masalah[] = 'Daftar transaksi yang tersimpan di blok tidak sesuai merkle root.';
        }

        $aktual = [];
        foreach ($blok->kueriTransaksi($akhirSebelumnya)->cursor() as $baris) {
            $aktual[(int) $baris->id] = $baris->hitungHash($baris->hash_sebelumnya);
        }

        if (MerkleTree::akar(array_values($aktual)) === $blok->merkle_root) {
            return $masalah;
        }

        foreach ($tersimpan as $id => $hash) {
            if (! array_key_exists($id, $aktual)) {
                $masalah[] = "Catatan audit #{$id} dihapus dari basis data.";
            } elseif (! hash_equals($hash, $aktual[$id])) {
                $masalah[] = "Catatan audit #{$id} diubah setelah masuk blok.";
            }
        }

        foreach (array_diff_key($aktual, $tersimpan) as $id => $_) {
            $masalah[] = "Catatan audit #{$id} disisipkan ke dalam blok setelah ditambang.";
        }

        if ($masalah === []) {
            $masalah[] = 'Isi blok tidak sesuai merkle root.';
        }

        return $masalah;
    }

    /**
     * Rincian isi satu blok untuk Block Explorer: tiap catatan beserta hash daun
     * tersegel, hash yang dihitung ulang sekarang, dan bukti Merkle-nya.
     *
     * Kolom nilai_lama/nilai_baru sengaja tidak dikembalikan — isinya memuat
     * nomor rekening dan data nasabah lain, dan explorer tidak membutuhkannya.
     *
     * @return list<array{
     *     id:int, aksi:?string, username:?string, objek:?string, waktu:mixed,
     *     daun:?string, daun_aktual:?string, status:string, bukti:list<array{hash:string, posisi:string}>, bukti_sah:bool
     * }>
     */
    public function rincianTransaksi(BlokAudit $blok, ?int $akhirSebelumnya): array
    {
        $daun = $blok->daunTersimpan();
        $indeks = [];
        foreach ($blok->daftar_transaksi ?? [] as $i => [$id, $_]) {
            $indeks[(int) $id] = $i;
        }

        $aktual = $blok->kueriTransaksi($akhirSebelumnya)->get()->keyBy('id');
        $semuaId = array_unique(array_merge(array_keys($indeks), $aktual->keys()->all()));
        sort($semuaId);

        $rincian = [];
        foreach ($semuaId as $id) {
            /** @var AuditTrail|null $baris */
            $baris = $aktual->get($id);
            $posisi = $indeks[$id] ?? null;
            $daunTersegel = $posisi !== null ? $daun[$posisi] : null;
            $daunAktual = $baris?->hitungHash($baris->hash_sebelumnya);

            $status = match (true) {
                $baris === null => 'dihapus',
                $posisi === null => 'disisipkan',
                ! hash_equals((string) $daunTersegel, (string) $daunAktual) => 'diubah',
                default => 'utuh',
            };

            $bukti = $posisi !== null ? MerkleTree::bukti($daun, $posisi) : [];

            $rincian[] = [
                'id' => $id,
                'aksi' => $baris?->aksi,
                'username' => $baris?->username,
                'objek' => $baris?->auditable_type
                    ? class_basename($baris->auditable_type).' #'.$baris->auditable_id
                    : null,
                'waktu' => $baris?->created_at,
                'daun' => $daunTersegel,
                'daun_aktual' => $daunAktual,
                'status' => $status,
                'bukti' => $bukti,
                // Bukti dicoba dengan hash yang dihitung dari isi SEKARANG: hanya
                // catatan yang tidak berubah yang sampai tepat ke merkle root.
                'bukti_sah' => $daunAktual !== null && $posisi !== null
                    && MerkleTree::verifikasi($daunAktual, $bukti, $blok->merkle_root),
            ];
        }

        return $rincian;
    }
}
