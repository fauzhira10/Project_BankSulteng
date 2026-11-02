<?php

use App\Models\Jurnal;
use App\Models\MasterCabang;
use App\Models\MasterTransaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Hindari error "Vite manifest not found" saat test dijalankan tanpa asset build
uses()->beforeEach(function () {
    $this->withoutVite();
})->in(__FILE__);

// ============================================================
//  SERAH TERIMA — Frontend Test
//  Memverifikasi render halaman index & cetak dari sisi UI/HTTP
// ============================================================

/**
 * Helper: buat admin user.
 */
function adminUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

/**
 * Helper: buat tiket jurnal dengan status CLOSED.
 */
function tiketClosed(array $override = []): Jurnal
{
    $cabang    = MasterCabang::firstOrCreate(['kode_cabang' => 'FE01'], ['nama_cabang' => 'Cabang Frontend Test']);
    $transaksi = MasterTransaksi::firstOrCreate(['jenis_transaksi' => 'Tarik Tunai'], ['channel' => 'ATM']);

    return Jurnal::create(array_merge([
        'master_cabang_id'    => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'no_tiket'            => 'BS-202609030099',
        'no_resi'             => 'RESI-FE-001',
        'nama_nasabah'        => 'MUSDALIFAH',
        'no_rekening'         => '0010205293011',
        'nominal_transaksi'   => 3_750_000,
        'status'              => 'CLOSED',
        'tgl_transaksi'       => '2026-09-03',
        'tgl_terima'          => '2026-09-03',
        'tgl_selesai'         => '2026-09-05',
    ], $override));
}

/* ──────────────────────────────────────────────────────────
   1. HALAMAN INDEX — HTTP 200 dan view benar
────────────────────────────────────────────────────────── */

test('[Frontend] Halaman index serah terima dapat diakses oleh admin dengan status 200', function () {
    $admin    = adminUser();
    $response = $this->actingAs($admin)->get(route('admin.serah-terima.index'));

    $response->assertOk();
    $response->assertViewIs('admin.serah_terima.index');
});

test('[Frontend] Halaman index mengirimkan variabel tikets, ringkasan, dan cabangs ke view', function () {
    $admin    = adminUser();
    $response = $this->actingAs($admin)->get(route('admin.serah-terima.index'));

    $response->assertViewHas('tikets');
    $response->assertViewHas('ringkasan');
    $response->assertViewHas('cabangs');
});

test('[Frontend] Halaman index mengandung elemen interaktif checkbox dan tombol cetak', function () {
    tiketClosed();
    $admin    = adminUser();
    $response = $this->actingAs($admin)->get(route('admin.serah-terima.index'));

    $response->assertOk();
    // Pastikan ada form POST ke route cetak
    $response->assertSee('formSerahTerima', false);
    // Pastikan ada checkbox tiket
    $response->assertSee('name="ids[]"', false);
    // Pastikan floating summary bar ada di DOM
    $response->assertSee('floatingSummary', false);
    // Pastikan tombol check-all ada
    $response->assertSee('btnCheckAll', false);
});

test('[Frontend] Halaman index memuat input pencarian keyword dan tanggal pembukuan', function () {
    $admin    = adminUser();
    $response = $this->actingAs($admin)->get(route('admin.serah-terima.index'));

    $response->assertSee('inputSearch', false);
    $response->assertSee('tanggal_serah_terima', false);
});

test('[Frontend] Halaman index menampilkan data tiket selesai pada tabel', function () {
    $tiket = tiketClosed(['nama_nasabah' => 'MUSDALIFAH', 'no_tiket' => 'BS-202609030021']);
    $admin = adminUser();

    $response = $this->actingAs($admin)->get(route('admin.serah-terima.index'));

    $response->assertOk();
    $response->assertSee('MUSDALIFAH');
    $response->assertSee('BS-202609030021');
});

/* ──────────────────────────────────────────────────────────
   2. HALAMAN CETAK — Render layout A4 yang benar
────────────────────────────────────────────────────────── */

test('[Frontend] Halaman cetak A4 berhasil dirender untuk tiket terpilih', function () {
    $admin = adminUser();
    $tiket = tiketClosed();

    $response = $this->actingAs($admin)->post(route('admin.serah-terima.cetak'), [
        'ids'                  => [$tiket->id],
        'tanggal_serah_terima' => '2026-09-18',
    ]);

    $response->assertOk();
    $response->assertViewIs('admin.serah_terima.cetak');
});

test('[Frontend] Halaman cetak mengandung judul resmi dan tanggal Indonesia', function () {
    $admin = adminUser();
    $tiket = tiketClosed();

    $response = $this->actingAs($admin)->post(route('admin.serah-terima.cetak'), [
        'ids'                  => [$tiket->id],
        'tanggal_serah_terima' => '2026-09-18',
    ]);

    $response->assertSee('SERAH TERIMA PENYELESAIAN NASABAH', false);
    $response->assertSee('18 SEPTEMBER 2026', false);
});

test('[Frontend] Halaman cetak memuat toolbar dengan tombol kembali dan cetak dokumen', function () {
    $admin = adminUser();
    $tiket = tiketClosed();

    $response = $this->actingAs($admin)->post(route('admin.serah-terima.cetak'), [
        'ids'                  => [$tiket->id],
        'tanggal_serah_terima' => '2026-09-18',
    ]);

    $response->assertSee('Kembali', false);
    $response->assertSee('btnCetakDokumen', false);
    $response->assertSee('window.print()', false);
});

test('[Frontend] Halaman cetak memuat data nasabah yang dipilih pada tabel A4', function () {
    $admin = adminUser();
    $tiket = tiketClosed([
        'nama_nasabah'      => 'MUSDALIFAH',
        'no_tiket'          => 'BS-202609030021',
        'no_rekening'       => '0010205293011',
        'nominal_transaksi' => 3_750_000,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.serah-terima.cetak'), [
        'ids'                  => [$tiket->id],
        'tanggal_serah_terima' => '2026-09-18',
    ]);

    $response->assertSee('MUSDALIFAH', false);
    $response->assertSee('BS-202609030021', false);
    $response->assertSee('0010205293011', false);
    $response->assertSee('3.750.000', false); // format Rupiah
    $response->assertSee('CLOSED', false);
});

test('[Frontend] Halaman cetak memuat baris TOTAL DIBUKUKAN di footer tabel', function () {
    $admin = adminUser();
    $tiket = tiketClosed(['nominal_transaksi' => 3_750_000]);

    $response = $this->actingAs($admin)->post(route('admin.serah-terima.cetak'), [
        'ids'                  => [$tiket->id],
        'tanggal_serah_terima' => '2026-09-18',
    ]);

    $response->assertSee('TOTAL DIBUKUKAN', false);
    $response->assertSee('3.750.000', false);
});

test('[Frontend] Halaman cetak memuat kolom tanda tangan Yang Menerima', function () {
    $admin = adminUser();
    $tiket = tiketClosed();

    $response = $this->actingAs($admin)->post(route('admin.serah-terima.cetak'), [
        'ids'                  => [$tiket->id],
        'tanggal_serah_terima' => '2026-09-18',
    ]);

    $response->assertSee('Yang Menerima', false);
    $response->assertDontSee('Yang Menyerahkan', false);
});

test('[Frontend] Halaman cetak memuat stempel audit blockchain di footer dokumen', function () {
    $admin = adminUser();
    $tiket = tiketClosed();

    $response = $this->actingAs($admin)->post(route('admin.serah-terima.cetak'), [
        'ids'                  => [$tiket->id],
        'tanggal_serah_terima' => '2026-09-18',
    ]);

    $response->assertSee('Blockchain Hash', false);
    $response->assertSee('E-Jurnal Keluhan', false);
});

test('[Frontend] Halaman cetak memuat style @media print untuk menyembunyikan toolbar', function () {
    $admin = adminUser();
    $tiket = tiketClosed();

    $response = $this->actingAs($admin)->post(route('admin.serah-terima.cetak'), [
        'ids'                  => [$tiket->id],
        'tanggal_serah_terima' => '2026-09-18',
    ]);

    $response->assertSee('@media print', false);
    $response->assertSee('display: none', false);
});

/* ──────────────────────────────────────────────────────────
   3. VALIDASI FRONTEND — Error state
────────────────────────────────────────────────────────── */

test('[Frontend] Cetak gagal validasi saat tidak ada tiket yang dikirim', function () {
    $admin    = adminUser();
    $response = $this->actingAs($admin)->post(route('admin.serah-terima.cetak'), [
        'ids' => [],
    ]);

    $response->assertSessionHasErrors('ids');
});

test('[Frontend] Pengguna CS tidak dapat mengakses halaman serah terima (redirect)', function () {
    $cs       = User::factory()->create(['role' => 'cs']);
    $response = $this->actingAs($cs)->get(route('admin.serah-terima.index'));

    $response->assertRedirect(route('cs.dashboard'));
});

test('[Frontend] Pengguna tamu dialihkan ke halaman login', function () {
    $response = $this->get(route('admin.serah-terima.index'));

    $response->assertRedirect(route('login'));
});

/* ──────────────────────────────────────────────────────────
   4. SIDEBAR — Verifikasi menu "Serah Terima" ada di layout
────────────────────────────────────────────────────────── */

test('[Frontend] Sidebar admin mengandung link menu Serah Terima', function () {
    $admin    = adminUser();
    // Gunakan halaman index serah terima (juga menggunakan layout app dengan sidebar)
    // menghindari halaman dashboard yang mengandung query YEAR() MySQL (tidak kompatibel SQLite)
    $response = $this->actingAs($admin)->get(route('admin.serah-terima.index'));

    $response->assertOk();
    // Sidebar harus me-render link ke halaman serah terima
    $response->assertSee(route('admin.serah-terima.index'), false);
    $response->assertSee('Serah Terima', false);
});
