<?php

use App\Http\Controllers\Admin\BlockchainController;
use App\Http\Controllers\Admin\PejabatTtdController;
use App\Http\Controllers\Admin\PengaduanMasukController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\AtmMonitoringController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Cs\DashboardController as CsDashboardController;
use App\Http\Controllers\Cs\PengaduanController as CsPengaduanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PengaduanLampiranController;
use Illuminate\Support\Facades\Route;

// Rute Autentikasi (Hanya untuk Tamu / Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    // throttle: pagar kasar per alamat IP terhadap endpoint login. Penguncian
    // per akun (username + IP) ditangani AuthController::pastikanBelumTerkunci().
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:20,1')
        ->name('login.post');
});

// ============================================================
// Rute Bersama (Admin Pusat & CS Cabang — Wajib Login)
// ============================================================
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // API AJAX Auto-Fill Biaya Admin & Channel (dipakai form jurnal admin & form pengaduan CS)
    Route::get('/api/transaksi/{id}', [JurnalController::class, 'getDetailTransaksi'])->name('api.transaksi.detail');

    // API AJAX Deteksi Keluhan Berulang (Nama Nasabah + No. Resi).
    // Awalan /api/duplikat sengaja dipisah dari /api/jurnal/{id} yang tanpa whereNumber,
    // supaya tidak tertangkap sebagai parameter {id}.
    Route::get('/api/duplikat/periksa', [JurnalController::class, 'cekDuplikat'])->name('api.duplikat.periksa');

    // Lihat lampiran pengaduan (PDF privat, akses dicek lewat PengaduanPolicy)
    Route::get('/pengaduan/{pengaduan}/lampiran/{lampiran}', [PengaduanLampiranController::class, 'show'])
        ->name('pengaduan.lampiran.show')
        ->whereNumber('pengaduan')
        ->whereNumber('lampiran');
});

// ============================================================
// Rute Admin Pusat (Divisi IT) — Sistem Jurnal Keluhan
// ============================================================
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Halaman Dashboard Utama (Home)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.home');

    // Halaman Form Input Jurnal Keluhan (mendukung ?pengaduan={id} untuk prefill dari pengaduan CS)
    Route::get('/jurnal/input', [JurnalController::class, 'create'])->name('jurnal.create');

    // Halaman Data Keluhan & Pencarian Jurnal
    Route::get('/jurnal/data', [JurnalController::class, 'index'])->name('jurnal.index');

    // Halaman Preview Jurnal (Setelah Input)
    Route::get('/jurnal/{id}/preview', [JurnalController::class, 'preview'])->name('jurnal.preview')->whereNumber('id');

    // Route Export Excel (.xlsx) Multi-Sheet
    Route::get('/jurnal/export-excel', [JurnalController::class, 'exportExcel'])->name('jurnal.export_excel');

    // Route Import Excel (.xlsx / .xls) Multi-Sheet
    Route::post('/jurnal/import-excel', [JurnalController::class, 'importExcel'])->name('jurnal.import_excel');

    // Route Simpan Data Jurnal Keluhan
    Route::post('/jurnal/simpan', [JurnalController::class, 'store'])->name('jurnal.store');

    // Route Reset / Kosongkan Seluruh Data Jurnal & Template.
    // Dibatasi Admin Utama: aksi ini menghapus seluruh tabel jurnals sekaligus
    // dan tidak dapat dibatalkan. Controller masih meminta konfirmasi kata sandi.
    Route::delete('/jurnal/reset-all', [JurnalController::class, 'resetAllData'])
        ->middleware('role:superadmin')
        ->name('jurnal.reset_all');

    // Route Edit & Update Data Jurnal Keluhan
    Route::get('/jurnal/{id}/edit', [JurnalController::class, 'edit'])->name('jurnal.edit')->whereNumber('id');
    Route::put('/jurnal/{id}', [JurnalController::class, 'update'])->name('jurnal.update')->whereNumber('id');

    // Route Hapus Data Jurnal Keluhan
    Route::delete('/jurnal/{id}', [JurnalController::class, 'destroy'])->name('jurnal.destroy')->whereNumber('id');

    // Route Menu Laporan Rekapitulasi Penyelesaian Keluhan Nasabah (Bulanan & Tahunan)
    Route::get('/laporan/rekapitulasi', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export-excel', [LaporanController::class, 'exportExcel'])->name('laporan.export_excel');

    // Route Menu Monitoring & Rekapitulasi Keluhan Mesin ATM
    Route::get('/atm-monitoring', [AtmMonitoringController::class, 'index'])->name('atm.index');

    // Route API AJAX Daftar Mesin ATM per Cabang
    Route::get('/api/cabang/{id}/atms', [JurnalController::class, 'getAtmsByCabang'])->name('api.cabang.atms');

    // Route Unduh Dokumen Jurnal (Otomatis deteksi LOKAL / ATMB)
    Route::get('/jurnal/{id}/download', [JurnalController::class, 'downloadDokumen'])->name('jurnal.download')->whereNumber('id');

    // Route Update Keterangan Log (AJAX dari Halaman Cetak/Data)
    Route::post('/jurnal/{id}/update-log', [JurnalController::class, 'updateLog'])->name('jurnal.update_log')->whereNumber('id');

    // Route API AJAX Rincian Jurnal Keluhan
    Route::get('/api/jurnal/{id}', [JurnalController::class, 'getDetailJurnal'])->name('api.jurnal.detail');

    // Rincian satu pengaduan untuk modal riwayat pada panel keluhan berulang.
    // Dipisah dari halaman /pengaduan-masuk/{id} karena hanya mengembalikan JSON.
    Route::get('/api/pengaduan/{pengaduan}', [PengaduanMasukController::class, 'detailJson'])
        ->name('api.pengaduan.detail')
        ->whereNumber('pengaduan');

    // ---------- Pengaduan Masuk dari CS Cabang ----------
    Route::prefix('pengaduan-masuk')->name('admin.pengaduan.')->group(function () {
        Route::get('/', [PengaduanMasukController::class, 'index'])->name('index');
        Route::get('/{pengaduan}', [PengaduanMasukController::class, 'show'])->name('show')->whereNumber('pengaduan');
        Route::post('/{pengaduan}/terima', [PengaduanMasukController::class, 'terima'])->name('terima')->whereNumber('pengaduan');
        Route::post('/{pengaduan}/tolak', [PengaduanMasukController::class, 'tolak'])->name('tolak')->whereNumber('pengaduan');
    });

    // ---------- Master Pejabat & Tanda Tangan Digital ----------
    Route::prefix('pejabat-ttd')->name('admin.pejabat-ttd.')->group(function () {
        Route::get('/', [PejabatTtdController::class, 'index'])->name('index');
        Route::post('/', [PejabatTtdController::class, 'store'])->name('store');
        Route::put('/{id}', [PejabatTtdController::class, 'update'])->name('update')->whereNumber('id');
        Route::delete('/{id}', [PejabatTtdController::class, 'destroy'])->name('destroy')->whereNumber('id');
        Route::post('/{id}/set-default', [PejabatTtdController::class, 'setDefault'])->name('set_default')->whereNumber('id');
    });

    // API AJAX Pejabat & Snapshot TTD Jurnal
    Route::get('/api/pejabat-ttd/slot/{slot}', [PejabatTtdController::class, 'apiBySlot'])->name('api.pejabat_ttd.slot')->whereNumber('slot');
    Route::post('/api/jurnal/{id}/ttd', [PejabatTtdController::class, 'simpanTtdJurnal'])->name('api.jurnal.simpan_ttd')->whereNumber('id');

    // ---------- Blockchain Jejak Audit (Block Explorer) ----------
    Route::prefix('blockchain')->name('admin.blockchain.')->group(function () {
        Route::get('/', [BlockchainController::class, 'index'])->name('index');
        Route::get('/blok/{nomor}', [BlockchainController::class, 'show'])->name('show')->whereNumber('nomor');
        Route::post('/tambang', [BlockchainController::class, 'tambang'])->name('tambang');
        Route::post('/jangkarkan', [BlockchainController::class, 'jangkarkan'])->name('jangkarkan');
    });

    // ---------- Manajemen Pengguna (Khusus Admin Utama / Super Admin) ----------
    Route::prefix('pengguna')->name('admin.pengguna.')->middleware('role:superadmin')->group(function () {
        Route::get('/', [PenggunaController::class, 'index'])->name('index');
        Route::get('/tambah', [PenggunaController::class, 'create'])->name('create');
        Route::post('/', [PenggunaController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [PenggunaController::class, 'edit'])->name('edit')->whereNumber('user');
        Route::put('/{user}', [PenggunaController::class, 'update'])->name('update')->whereNumber('user');
        Route::patch('/{user}/toggle-aktif', [PenggunaController::class, 'toggleAktif'])->name('toggle_aktif')->whereNumber('user');
        Route::post('/{user}/reset-sesi', [PenggunaController::class, 'resetSesi'])->name('reset_sesi')->whereNumber('user');
    });
});

// ============================================================
// Rute Customer Service Cabang — Pengaduan Nasabah
// ============================================================
Route::middleware(['auth', 'role:cs'])->prefix('cs')->name('cs.')->group(function () {
    Route::get('/', [CsDashboardController::class, 'index'])->name('dashboard');

    Route::get('/pengaduan', [CsPengaduanController::class, 'index'])->name('pengaduan.index');
    Route::get('/pengaduan/input', [CsPengaduanController::class, 'create'])->name('pengaduan.create');
    Route::post('/pengaduan', [CsPengaduanController::class, 'store'])->name('pengaduan.store');
    Route::get('/pengaduan/{pengaduan}', [CsPengaduanController::class, 'show'])->name('pengaduan.show')->whereNumber('pengaduan');
    Route::get('/pengaduan/{pengaduan}/edit', [CsPengaduanController::class, 'edit'])->name('pengaduan.edit')->whereNumber('pengaduan');
    Route::put('/pengaduan/{pengaduan}', [CsPengaduanController::class, 'update'])->name('pengaduan.update')->whereNumber('pengaduan');
    Route::delete('/pengaduan/{pengaduan}', [CsPengaduanController::class, 'destroy'])->name('pengaduan.destroy')->whereNumber('pengaduan');
    Route::delete('/pengaduan/{pengaduan}/lampiran/{lampiran}', [CsPengaduanController::class, 'destroyLampiran'])
        ->name('pengaduan.lampiran.destroy')
        ->whereNumber('pengaduan')
        ->whereNumber('lampiran');
});
