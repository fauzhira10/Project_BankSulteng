<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jurnal;
use App\Models\MasterCabang;
use App\Models\MasterPejabatTtd;
use App\Models\SerahTerima;
use App\Services\SerahTerimaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SerahTerimaController extends Controller
{
    /**
     * Menampilkan daftar tiket pengaduan selesai yang siap diserahterimakan / dibukukan.
     */
    public function index(Request $request)
    {
        $query = SerahTerimaService::ambilTiketSelesai($request->all());

        // Ambil data untuk tabel pemilihan tiket
        $tikets = $query->paginate(50)->withQueryString();

        // Ringkasan global & analitik tiket yang sudah selesai
        $semuaSelesai = SerahTerimaService::ambilTiketSelesai()->get();
        $ringkasan = SerahTerimaService::kalkulasiTotal($semuaSelesai);
        $analitik = SerahTerimaService::kalkulasiAnalitik($semuaSelesai);

        $cabangs = MasterCabang::orderBy('nama_cabang')->get();

        return view('admin.serah_terima.index', [
            'tikets' => $tikets,
            'ringkasan' => $ringkasan,
            'analitik' => $analitik,
            'cabangs' => $cabangs,
            'filters' => $request->only(['q', 'tgl_selesai', 'master_cabang_id']),
        ]);
    }

    /**
     * Memproses data tiket terpilih dan menampilkan dokumen cetak resmi A4.
     */
    public function cetak(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'tanggal_serah_terima' => 'nullable|date',
        ], [
            'ids.required' => 'Pilih minimal satu tiket pengaduan untuk dibuatkan lembar serah terima.',
            'ids.min' => 'Pilih minimal satu tiket pengaduan untuk dibuatkan lembar serah terima.',
        ]);

        $ids = array_values(array_map('intval', $request->input('ids', [])));

        $jurnals = Jurnal::with(['masterCabang', 'masterTransaksi', 'pengaduan'])
            ->whereIn('id', $ids)
            ->orderBy('tgl_selesai')
            ->orderBy('id')
            ->get();

        if ($jurnals->isEmpty()) {
            return redirect()
                ->route('admin.serah-terima.index')
                ->with('error', 'Tiket yang dipilih tidak ditemukan atau belum selesai.');
        }

        $total = SerahTerimaService::kalkulasiTotal($jurnals);

        $tanggalInput = $request->filled('tanggal_serah_terima')
            ? Carbon::parse($request->input('tanggal_serah_terima'))
            : now();

        $tanggalIndonesia = strtoupper($tanggalInput->locale('id')->translatedFormat('d F Y'));
        $nomorDokumen = SerahTerimaService::buatNomorDokumen($tanggalInput);

        $blockchainHash = SerahTerimaService::kunciKeBlockchain([
            'nomor_dokumen' => $nomorDokumen,
            'tanggal_serah_terima' => $tanggalInput->toDateString(),
            'total_nominal' => $total['total_nominal'],
            'tiket_ids' => $ids,
        ]);

        // Simpan riwayat serah terima ke database
        $serahTerima = SerahTerima::create([
            'nomor_dokumen' => $nomorDokumen,
            'tanggal_serah_terima' => $tanggalInput->toDateString(),
            'total_tiket' => $total['total_tiket'],
            'total_nominal' => $total['total_nominal'],
            'tiket_ids' => $ids,
            'blockchain_hash' => $blockchainHash,
            'created_by' => auth()->id(),
        ]);

        $pejabatTtd = MasterPejabatTtd::getAktifGrouped();

        return view('admin.serah_terima.cetak', [
            'serahTerima' => $serahTerima,
            'jurnals' => $jurnals,
            'total' => $total,
            'tanggalInput' => $tanggalInput,
            'tanggalIndonesia' => $tanggalIndonesia,
            'nomorDokumen' => $nomorDokumen,
            'blockchainHash' => $blockchainHash,
            'pejabatTtd' => $pejabatTtd,
        ]);
    }

    /**
     * Mengekspor data tiket terpilih ke dalam berkas CSV Pembukuan resmi Bank Sulteng.
     */
    public function exportCsv(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'tanggal_serah_terima' => 'nullable|date',
        ], [
            'ids.required' => 'Pilih minimal satu tiket pengaduan untuk diekspor ke CSV.',
            'ids.min' => 'Pilih minimal satu tiket pengaduan untuk diekspor ke CSV.',
        ]);

        $ids = array_values(array_map('intval', $request->input('ids', [])));

        $jurnals = Jurnal::with(['masterCabang', 'masterTransaksi', 'pengaduan'])
            ->whereIn('id', $ids)
            ->orderBy('tgl_selesai')
            ->orderBy('id')
            ->get();

        if ($jurnals->isEmpty()) {
            return redirect()
                ->route('admin.serah-terima.index')
                ->with('error', 'Tiket yang dipilih tidak ditemukan atau belum selesai.');
        }

        $tanggalInput = $request->filled('tanggal_serah_terima')
            ? Carbon::parse($request->input('tanggal_serah_terima'))
            : now();

        $csvContent = SerahTerimaService::generateCsvPembukuan($jurnals, $tanggalInput);
        $filename = 'Serah_Terima_Pembukuan_' . $tanggalInput->format('Ymd_His') . '.csv';

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
