<?php

use App\Models\Jurnal;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dokumen keluhan dengan status Menunggu tidak dapat dicetak dan diredirect dengan pesan error', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $jurnal = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'nama_nasabah' => 'Budi Santoso',
        'no_rekening' => '1234567890',
        'no_kartu' => '5000123456789012',
        'no_resi' => '998877',
        'no_tiket' => 'TKT-2026-0001',
        'nominal_transaksi' => 500000,
        'biaya_admin' => 0,
        'status' => 'Menunggu',
        'tgl_transaksi' => '2026-09-01',
        'tgl_terima' => '2026-09-01',
        'keterangan_log' => 'Saldo terdebet pada switching',
    ]);

    $response = $this->actingAs($admin)->get(route('jurnal.download', $jurnal->id));

    $response->assertRedirect(route('jurnal.preview', $jurnal->id));
    $response->assertSessionHas('error');
});

test('dokumen keluhan dengan status Done atau Success dapat dicetak secara normal', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $jurnal = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'nama_nasabah' => 'Siti Rahma',
        'no_rekening' => '0987654321',
        'no_kartu' => '5000987654321098',
        'no_resi' => '112233',
        'no_tiket' => 'TKT-2026-0002',
        'nominal_transaksi' => 300000,
        'biaya_admin' => 0,
        'status' => 'Done',
        'tgl_transaksi' => '2026-09-02',
        'tgl_terima' => '2026-09-02',
        'keterangan_log' => 'Uang telah dikreditkan kembali ke rekening',
    ]);

    $response = $this->actingAs($admin)->get(route('jurnal.download', $jurnal->id));

    $response->assertStatus(200);
});

test('halaman preview mengunci tombol cetak jika status masih Menunggu', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $jurnal = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'nama_nasabah' => 'Ahmad Fauzi',
        'no_rekening' => '1122334455',
        'no_kartu' => '5000112233445566',
        'no_resi' => '554433',
        'no_tiket' => 'TKT-2026-0003',
        'nominal_transaksi' => 100000,
        'biaya_admin' => 0,
        'status' => 'Menunggu',
        'tgl_transaksi' => '2026-09-03',
        'tgl_terima' => '2026-09-03',
        'keterangan_log' => 'Sedang menunggu log switching',
    ]);

    $response = $this->actingAs($admin)->get(route('jurnal.preview', $jurnal->id));

    $response->assertStatus(200);
    $response->assertSee('Status Menunggu — Belum Dapat Dicetak 🔒');
    $response->assertDontSee('Cetak Form Normal (Klaim Diterima)');
});

test('tabel data keluhan menampilkan tombol Menunggu terkunci untuk keluhan berstatus Menunggu', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'nama_nasabah' => 'Dewi Lestari',
        'no_rekening' => '5566778899',
        'no_kartu' => '5000556677889900',
        'no_resi' => '778899',
        'no_tiket' => 'TKT-2026-0004',
        'nominal_transaksi' => 250000,
        'biaya_admin' => 0,
        'status' => 'Menunggu',
        'tgl_transaksi' => '2026-09-04',
        'tgl_terima' => '2026-09-04',
        'keterangan_log' => 'Pemeriksaan log mesin',
    ]);

    $response = $this->actingAs($admin)->get(route('jurnal.index'));

    $response->assertStatus(200);
    $response->assertSee('Menunggu 🔒');
    $response->assertSee('showMenungguWarning');
});
