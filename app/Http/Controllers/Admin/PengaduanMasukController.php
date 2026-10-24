<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PengaduanStatus;
use App\Http\Controllers\Controller;
use App\Models\MasterCabang;
use App\Models\Pengaduan;
use App\Services\AuditTrailService;
use App\Services\DeteksiDuplikatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Admin Pusat: meninjau pengaduan yang dikirim CS cabang,
 * menerima / menolak, lalu memasukkannya ke Jurnal Keluhan.
 */
class PengaduanMasukController extends Controller
{
    /**
     * Daftar pengaduan masuk (tab per status, default: Terkirim)
     */
    public function index(Request $request)
    {
        $statusAktif = $request->input('status', PengaduanStatus::Terkirim->value);
        if ($statusAktif !== 'semua' && ! in_array($statusAktif, PengaduanStatus::values(), true)) {
            $statusAktif = PengaduanStatus::Terkirim->value;
        }

        $query = Pengaduan::with(['cabang', 'transaksi', 'user', 'jurnal'])
            ->cari($request->q);

        if ($statusAktif !== 'semua') {
            $query->status($statusAktif);
        }

        if ($request->filled('master_cabang_id')) {
            $query->where('master_cabang_id', $request->master_cabang_id);
        }
        if ($request->filled('tgl_dari')) {
            $query->whereDate('created_at', '>=', $request->tgl_dari);
        }
        if ($request->filled('tgl_sampai')) {
            $query->whereDate('created_at', '<=', $request->tgl_sampai);
        }

        $pengaduans = $query->latest('id')->paginate(15)->withQueryString();

        // Jumlah per status untuk tab (tanpa filter lain agar konsisten)
        $perStatus = Pengaduan::query()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $counts = ['semua' => (int) $perStatus->sum()];
        foreach (PengaduanStatus::cases() as $status) {
            $counts[$status->value] = (int) ($perStatus[$status->value] ?? 0);
        }

        $cabangs = MasterCabang::orderBy('kode_cabang')->get();

        return view('admin.pengaduan_index', compact('pengaduans', 'counts', 'statusAktif', 'cabangs'));
    }

    /**
     * Detail pengaduan + panel tindak lanjut
     */
    /**
     * Rincian satu pengaduan dalam bentuk JSON, untuk modal riwayat pada panel
     * keluhan berulang.
     *
     * Panel itu dulu menanam seluruh baris pengaduan di dalam atribut
     * `data-pengaduan` setiap tombolnya, sehingga nomor rekening, nomor kartu,
     * NIK, dan nomor HP hingga lima nasabah sekaligus tercetak di sumber halaman
     * setiap kali panelnya muncul. Sekarang datanya diambil hanya untuk baris
     * yang benar-benar dibuka petugas.
     *
     * Bentuk balasannya sengaja sama persis dengan `json_encode($pengaduan)`
     * yang dulu tertanam, supaya skrip modalnya tidak perlu berubah.
     */
    public function detailJson(Pengaduan $pengaduan)
    {
        Gate::authorize('view', $pengaduan);

        return response()->json(
            $pengaduan->load('cabang:id,kode_cabang,nama_cabang')
        );
    }

    public function show(Pengaduan $pengaduan)
    {
        Gate::authorize('view', $pengaduan);

        $pengaduan->load(['cabang', 'transaksi', 'lampirans', 'user', 'penerima', 'jurnal.masterTransaksi']);

        // Peringatan bila keluhan dengan nama nasabah + no resi yang sama sudah pernah
        // ditangani, supaya admin tahu sebelum menekan "Input ke Jurnal".
        $duplikat = DeteksiDuplikatService::periksa(
            $pengaduan->nama_nasabah,
            $pengaduan->no_resi,
            null,
            $pengaduan->jurnal_id,
            $pengaduan->id
        );

        return view('admin.pengaduan_show', compact('pengaduan', 'duplikat'));
    }

    /**
     * Terima pengaduan: Terkirim → Diterima (data terkunci untuk CS)
     */
    public function terima(Request $request, Pengaduan $pengaduan)
    {
        Gate::authorize('terima', $pengaduan);

        $request->validate([
            'catatan_pusat' => 'nullable|string|max:2000',
        ]);

        $pengaduan->update([
            'status' => PengaduanStatus::Diterima,
            'diterima_oleh' => $request->user()->id,
            'diterima_at' => now(),
            'catatan_pusat' => $request->filled('catatan_pusat') ? trim($request->catatan_pusat) : $pengaduan->catatan_pusat,
        ]);

        AuditTrailService::catat('pengaduan.diterima', [
            'objek' => $pengaduan,
            'baru' => ['status' => PengaduanStatus::Diterima->value],
            'keterangan' => "Pengaduan {$pengaduan->nomor_tiket} dari {$pengaduan->labelCabang()} diterima Admin Pusat.",
        ]);

        return redirect()
            ->route('admin.pengaduan.show', $pengaduan)
            ->with('success', "Pengaduan {$pengaduan->nomor_tiket} telah diterima. Silakan lanjutkan dengan memasukkannya ke Jurnal Keluhan.");
    }

    /**
     * Tolak pengaduan (wajib catatan alasan) — hanya sebelum masuk jurnal
     */
    public function tolak(Request $request, Pengaduan $pengaduan)
    {
        Gate::authorize('tolak', $pengaduan);

        $request->validate([
            'catatan_pusat' => 'required|string|min:5|max:2000',
        ], [
            'catatan_pusat.required' => 'Alasan penolakan wajib diisi agar CS cabang mengetahui kekurangannya.',
            'catatan_pusat.min' => 'Alasan penolakan terlalu singkat (minimal :min karakter).',
        ]);

        $pengaduan->update([
            'status' => PengaduanStatus::Ditolak,
            'diterima_oleh' => $pengaduan->diterima_oleh ?? $request->user()->id,
            'diterima_at' => $pengaduan->diterima_at ?? now(),
            'ditolak_at' => now(),
            'catatan_pusat' => trim($request->catatan_pusat),
        ]);

        AuditTrailService::catat('pengaduan.ditolak', [
            'objek' => $pengaduan,
            'baru' => ['status' => PengaduanStatus::Ditolak->value],
            'keterangan' => "Pengaduan {$pengaduan->nomor_tiket} ditolak. Alasan: ".trim($request->catatan_pusat),
        ]);

        return redirect()
            ->route('admin.pengaduan.show', $pengaduan)
            ->with('success', "Pengaduan {$pengaduan->nomor_tiket} telah ditolak dan catatan dikirim ke CS cabang.");
    }
}
