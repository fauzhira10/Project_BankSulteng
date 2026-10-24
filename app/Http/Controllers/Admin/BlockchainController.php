<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlokAudit;
use App\Services\BlockchainAuditService;
use App\Services\JangkarEthereumService;
use App\Support\MerkleTree;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Block Explorer blockchain jejak audit.
 *
 * Menampilkan rantai blok, status keutuhan tiap blok, isi blok beserta pohon
 * Merkle dan buktinya, serta status jangkar Ethereum. Isi catatan audit
 * (nilai_lama / nilai_baru) tidak pernah ditampilkan di sini.
 */
class BlockchainController extends Controller
{
    public function __construct(
        protected BlockchainAuditService $blockchain,
        protected JangkarEthereumService $jangkar,
    ) {}

    public function index(): View
    {
        $pemeriksaan = $this->blockchain->periksa();

        $daftarBlok = BlokAudit::query()->orderByDesc('nomor')->paginate(15);

        // Lima blok terakhir untuk gambar rantai, urut kiri (lama) ke kanan (baru).
        $rantai = BlokAudit::query()->orderByDesc('nomor')->limit(5)->get()->reverse()->values();

        $ringkasJangkar = [
            'aktif' => $this->jangkar->aktif(),
            'url_kontrak' => $this->jangkar->urlKontrak(),
            'alamat_kontrak' => config('blockchain.ethereum.alamat_kontrak'),
            'terkirim' => BlokAudit::query()->where('jangkar_status', 'terkirim')->count(),
            'belum' => BlokAudit::query()->where(fn ($q) => $q->whereNull('jangkar_status')->orWhere('jangkar_status', 'gagal'))->count(),
            'konflik' => BlokAudit::query()->where('jangkar_status', 'konflik')->count(),
        ];

        return view('admin.blockchain.index', [
            'pemeriksaan' => $pemeriksaan,
            'daftarBlok' => $daftarBlok,
            'rantai' => $rantai,
            'jangkar' => $ringkasJangkar,
            'ukuranBlok' => (int) config('blockchain.ukuran_blok'),
            'tingkatKesulitan' => (int) config('blockchain.tingkat_kesulitan'),
        ]);
    }

    public function show(Request $request, int $nomor): View
    {
        $blok = BlokAudit::query()->where('nomor', $nomor)->firstOrFail();
        $sebelumnya = BlokAudit::query()->where('nomor', $nomor - 1)->first();
        $berikutnya = BlokAudit::query()->where('nomor', $nomor + 1)->first();

        $pemeriksaan = $this->blockchain->periksa()['blok'][$blok->id]
            ?? ['nomor' => $blok->nomor, 'valid' => false, 'masalah' => ['Blok tidak ikut terperiksa.']];

        // Pembandingan dengan Ethereum melibatkan jaringan, jadi hanya bila diminta.
        $cekJangkar = null;
        if ($request->boolean('cek')) {
            try {
                $cekJangkar = $this->jangkar->aktif()
                    ? $this->jangkar->bandingkan([$blok])[$blok->nomor]
                    : ['status' => 'nonaktif'];
            } catch (RuntimeException $e) {
                $cekJangkar = ['status' => 'galat', 'pesan' => $e->getMessage()];
            }
        }

        return view('admin.blockchain.show', [
            'blok' => $blok,
            'sebelumnya' => $sebelumnya,
            'berikutnya' => $berikutnya,
            'pemeriksaan' => $pemeriksaan,
            'transaksi' => $this->blockchain->rincianTransaksi($blok, $sebelumnya?->audit_id_akhir),
            'pohon' => MerkleTree::tingkat($blok->daunTersimpan()),
            'cekJangkar' => $cekJangkar,
            'jangkarAktif' => $this->jangkar->aktif(),
        ]);
    }

    public function tambang(): RedirectResponse
    {
        try {
            $baru = $this->blockchain->tambang();
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($baru === []) {
            return back()->with('success', 'Tidak ada catatan audit baru — semua sudah tersegel di dalam blok.');
        }

        $nomor = array_map(fn ($b) => '#'.$b->nomor, $baru);

        return back()->with('success', count($baru).' blok baru berhasil ditambang ('.implode(', ', $nomor).').');
    }

    public function jangkarkan(): RedirectResponse
    {
        if (! $this->jangkar->aktif()) {
            return back()->with('error', 'Penjangkaran Ethereum belum diaktifkan. Isi ETH_JANGKAR_AKTIF, ETH_RPC_URL, dan ETH_ALAMAT_KONTRAK di .env.');
        }

        $hasil = $this->jangkar->jangkarkanTertunda();

        if ($hasil['galat']) {
            $awal = $hasil['terkirim'] > 0 ? "{$hasil['terkirim']} blok terkirim, lalu " : '';

            return back()->with('error', $awal.'penjangkaran terhenti — '.$hasil['galat']);
        }

        if ($hasil['terkirim'] === 0) {
            return back()->with('success', 'Semua blok sudah tercatat di Ethereum.');
        }

        return back()->with('success', "{$hasil['terkirim']} blok berhasil dijangkarkan ke Ethereum.");
    }
}
