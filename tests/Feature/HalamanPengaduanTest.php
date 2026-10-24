<?php

use App\Enums\PengaduanStatus;
use App\Models\Jurnal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Smoke test: memastikan seluruh halaman modul pengaduan (CS & Admin) dapat dirender tanpa error.
 */
test('seluruh halaman cs dapat dirender', function () {
    Storage::fake('local');
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();
    $pengaduan = buatPengaduan($cs, $transaksi);

    $this->actingAs($cs)->get(route('cs.dashboard'))->assertStatus(200)->assertSee($pengaduan->nomor_tiket);
    $this->actingAs($cs)->get(route('cs.pengaduan.index'))->assertStatus(200)->assertSee('BUDI SANTOSO');
    $this->actingAs($cs)->get(route('cs.pengaduan.index', ['status' => 'Terkirim', 'q' => 'BUDI']))->assertStatus(200)->assertSee($pengaduan->nomor_tiket);
    $this->actingAs($cs)->get(route('cs.pengaduan.create'))->assertStatus(200)->assertSee('Kirim Pengaduan ke Pusat');
    $this->actingAs($cs)->get(route('cs.pengaduan.edit', $pengaduan))->assertStatus(200)->assertSee('Simpan Perubahan');
    $this->actingAs($cs)->get(route('cs.pengaduan.show', $pengaduan))->assertStatus(200)->assertSee('Menunggu Verifikasi Pusat');
});

test('seluruh halaman admin pengaduan dan pengguna dapat dirender', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $admin = buatSuperAdmin();
    $transaksi = buatTransaksi();
    $pengaduan = buatPengaduan($cs, $transaksi);

    $this->actingAs($admin)->get(route('admin.pengaduan.index'))->assertStatus(200);
    $this->actingAs($admin)->get(route('admin.pengaduan.index', ['status' => 'semua', 'master_cabang_id' => $cabang->id, 'tgl_dari' => '2026-01-01']))->assertStatus(200)->assertSee($pengaduan->nomor_tiket);
    $this->actingAs($admin)->get(route('admin.pengaduan.show', $pengaduan))->assertStatus(200)->assertSee('Terima & Input ke Jurnal');

    $this->actingAs($admin)->get(route('admin.pengguna.index'))->assertStatus(200)->assertSee($cs->name);
    $this->actingAs($admin)->get(route('admin.pengguna.create'))->assertStatus(200);
    $this->actingAs($admin)->get(route('admin.pengguna.edit', $cs))->assertStatus(200)->assertSee($cs->username);
});

test('tindakan penting memakai modal konfirmasi sistem, bukan dialog bawaan browser', function () {
    Storage::fake('local');
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $admin = buatSuperAdmin();
    $pengaduan = buatPengaduan($cs, buatTransaksi());
    $lampiran = buatLampiran($pengaduan);

    // Terima pengaduan
    $detail = $this->actingAs($admin)->get(route('admin.pengaduan.show', $pengaduan));
    $detail->assertStatus(200);
    $detail->assertSee('data-konfirmasi="Terima Pengaduan Ini?"', false);
    $detail->assertSee('id="modalKonfirmasi"', false);
    $detail->assertDontSee('return confirm(', false);

    // Tolak pengaduan memakai modal tersendiri berisi alasan
    $detail->assertSee('id="modalTolak"', false);
    $detail->assertSee('Alasan Penolakan');

    // Hapus lampiran pada form CS
    $form = $this->actingAs($cs)->get(route('cs.pengaduan.edit', $pengaduan));
    $form->assertStatus(200);
    $form->assertSee('data-konfirmasi="Hapus Lampiran Ini?"', false);
    $form->assertDontSee('return confirm(', false);

    // Nonaktifkan akun pengguna
    $pengguna = $this->actingAs($admin)->get(route('admin.pengguna.index'));
    $pengguna->assertStatus(200);
    $pengguna->assertSee('data-konfirmasi="Nonaktifkan Akun Ini?"', false);
    $pengguna->assertDontSee('return confirm(', false);

    expect($lampiran->exists)->toBeTrue();
});

test('halaman tidak menampilkan sintaks blade mentah', function () {
    Storage::fake('local');
    $cabang = buatCabang();
    $cs = buatCs($cabang, ['username' => 'cs.uji']);
    $admin = buatSuperAdmin();
    $pengaduan = buatPengaduan($cs, buatTransaksi());
    buatLampiran($pengaduan);

    // Penulisan @{{ ... }} pada Blade berarti "tampilkan kurung kurawal apa adanya",
    // sehingga mudah keliru dipakai saat ingin menampilkan tanda @ sebelum username.
    $halaman = [
        route('admin.pengaduan.show', $pengaduan),
        route('admin.pengaduan.index'),
        route('admin.pengguna.index'),
    ];

    foreach ($halaman as $url) {
        $response = $this->actingAs($admin)->get($url);
        $response->assertStatus(200);
        $response->assertDontSee('{{', false);
        $response->assertDontSee('$pengaduan->', false);
    }

    // Username pelapor tampil sebagai nilai, bukan sebagai kode.
    // Tanda @ ditulis sebagai entitas &#64; karena "@{{" pada Blade berarti
    // menampilkan kurung kurawal apa adanya.
    $this->actingAs($admin)->get(route('admin.pengaduan.show', $pengaduan))
        ->assertSee('&#64;cs.uji', false)
        ->assertDontSee("username ?? '-'", false);

    foreach ([route('cs.dashboard'), route('cs.pengaduan.index'), route('cs.pengaduan.show', $pengaduan)] as $url) {
        $this->actingAs($cs)->get($url)->assertStatus(200)->assertDontSee('{{', false);
    }
});

test('panel status manual sudah tidak ada pada halaman pengaduan masuk', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $admin = buatAdmin();
    $pengaduan = buatPengaduan($cs, buatTransaksi());

    $response = $this->actingAs($admin)->get(route('admin.pengaduan.show', $pengaduan));

    $response->assertStatus(200);
    $response->assertDontSee('Atur Status Manual');
    $response->assertDontSee('Ikuti Status Jurnal Lagi');
    $response->assertDontSee('Status Manual');

    expect(Route::has('admin.pengaduan.status'))->toBeFalse()
        ->and(Route::has('admin.pengaduan.status_otomatis'))->toBeFalse();
});

test('halaman jurnal yang tertaut pengaduan menampilkan sumber pengaduan', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $admin = buatAdmin();
    $transaksi = buatTransaksi();
    $pengaduan = buatPengaduan($cs, $transaksi);

    $jurnal = Jurnal::create([
        'nama_nasabah' => 'BUDI SANTOSO', 'no_resi' => '123456', 'no_rekening' => '00900001234', 'no_kartu' => '-',
        'no_tiket' => $pengaduan->nomor_tiket, 'master_cabang_id' => $cabang->id, 'master_transaksi_id' => $transaksi->id,
        'terminal_transaksi' => '-', 'nominal_transaksi' => 500000, 'biaya_admin' => 0,
        'tgl_transaksi' => '2026-09-01', 'tgl_terima' => '2026-09-04', 'tgl_selesai' => null,
        'status' => 'Menunggu', 'permasalahan' => '-', 'keterangan_log' => '-',
    ]);
    $pengaduan->update(['jurnal_id' => $jurnal->id, 'status' => PengaduanStatus::Diproses, 'diproses_at' => now()]);

    $this->actingAs($admin)->get(route('jurnal.preview', $jurnal->id))->assertStatus(200)->assertSee('Sumber: Pengaduan CS');
    $this->actingAs($admin)->get(route('jurnal.edit', $jurnal->id))->assertStatus(200)->assertSee('bersumber dari pengaduan CS');
    $this->actingAs($admin)->get(route('jurnal.index'))->assertStatus(200)->assertSee($pengaduan->nomor_tiket);
    $this->actingAs($admin)->get(route('admin.pengaduan.show', $pengaduan))->assertStatus(200)->assertSee('Tertaut ke Jurnal Keluhan');

    // CS melihat status "Dalam Proses Jurnal" dan info tiket dari pusat
    $this->actingAs($cs)->get(route('cs.pengaduan.show', $pengaduan))->assertStatus(200)->assertSee('Dalam Proses Jurnal')->assertSee($pengaduan->nomor_tiket);
});
