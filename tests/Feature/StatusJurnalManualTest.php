<?php

use App\Enums\PengaduanStatus;
use App\Models\Jurnal;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('form jurnal memakai isian status yang bisa diketik manual', function () {
    $admin = buatAdmin();
    buatCabang();
    buatTransaksi();

    $response = $this->actingAs($admin)->get(route('jurnal.create'));

    $response->assertStatus(200);
    $response->assertSee('name="status"', false);
    $response->assertSee('id="status_input"', false);
    $response->assertSee('Ketik bebas / pilih daftar');
    // Daftar status standar tetap tersedia sebagai pilihan cepat
    foreach (['Menunggu', 'Success', 'Done', 'Rejected'] as $baku) {
        $response->assertSee($baku);
    }
});

test('status keluhan dapat diketik manual saat menyimpan jurnal', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi, [
        'status' => 'Menunggu Konfirmasi Bank Lain',
    ]))->assertRedirect(route('jurnal.index'));

    expect(Jurnal::first()->status)->toBe('Menunggu Konfirmasi Bank Lain');
});

test('status manual dapat diubah lagi lewat form edit jurnal', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi));
    $jurnal = Jurnal::first();

    $halaman = $this->actingAs($admin)->get(route('jurnal.edit', $jurnal->id));
    $halaman->assertStatus(200)->assertSee('id="status_input"', false);

    $this->actingAs($admin)->put(route('jurnal.update', $jurnal->id), dataJurnal($cabang, $transaksi, [
        'status' => 'Sedang Investigasi Vendor ATM',
    ]))->assertRedirect(route('jurnal.index'));

    expect($jurnal->fresh()->status)->toBe('Sedang Investigasi Vendor ATM');
});

test('penulisan status baku diseragamkan dan spasi berlebih dirapikan', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    // "done" harus menjadi "Done" agar tetap terhitung pada ringkasan statistik
    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi, ['status' => '  done ']));
    expect(Jurnal::first()->status)->toBe('Done');

    // Spasi ganda pada status kustom dirapikan
    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi, [
        'no_resi' => '999000',
        'status' => 'Menunggu    Dokumen   Cabang',
    ]));
    expect(Jurnal::orderByDesc('id')->first()->status)->toBe('Menunggu Dokumen Cabang');
});

test('status kustom muncul sebagai pilihan filter dan datanya dapat disaring', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi, [
        'nama_nasabah' => 'Siti Aminah',
        'status' => 'Menunggu Konfirmasi Bank Lain',
    ]));

    $daftar = $this->actingAs($admin)->get(route('jurnal.index'));
    $daftar->assertStatus(200);
    $daftar->assertSee('Status Kustom (diketik manual)');
    $daftar->assertSee('Menunggu Konfirmasi Bank Lain');

    $tersaring = $this->actingAs($admin)->get(route('jurnal.index', ['status' => 'Menunggu Konfirmasi Bank Lain']));
    $tersaring->assertStatus(200)->assertSee('SITI AMINAH');

    $kosong = $this->actingAs($admin)->get(route('jurnal.index', ['status' => 'Rejected']));
    $kosong->assertStatus(200)->assertDontSee('SITI AMINAH');
});

test('status kustom pada jurnal membuat pengaduan cs berstatus dalam proses', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $admin = buatAdmin();
    $transaksi = buatTransaksi();
    $pengaduan = buatPengaduan($cs, $transaksi);

    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi, [
        'pengaduan_id' => $pengaduan->id,
        'status' => 'Menunggu Konfirmasi Bank Lain',
    ]));

    $pengaduan->refresh();
    expect($pengaduan->status)->toBe(PengaduanStatus::Diproses)
        ->and($pengaduan->jurnal_id)->not->toBeNull();

    // Diubah ke status baku penyelesaian → pengaduan ikut Selesai
    $pengaduan->jurnal->update(['status' => 'Done']);
    expect($pengaduan->fresh()->status)->toBe(PengaduanStatus::Selesai);
});

test('status kosong tetap tersimpan sebagai tanda strip', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi, ['status' => '-']));

    expect(Jurnal::first()->status)->toBe('-');
});
