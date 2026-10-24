<?php

use App\Models\Jurnal;
use App\Models\MasterCabang;
use App\Models\MasterTransaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('transaksi dengan jenis QRIS meskipun channel MOBILE BANKING selalu menggunakan template cetak.qris', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();

    // Master Transaksi QRIS dengan channel MOBILE BANKING (ID 29 di database riil)
    $transaksiQris = MasterTransaksi::create([
        'jenis_transaksi' => 'QRIS',
        'channel' => 'MOBILE BANKING',
        'biaya_admin' => 0,
    ]);

    $jurnal = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksiQris->id,
        'nama_nasabah' => 'SANTI',
        'no_rekening' => '1030204003021',
        'no_kartu' => '6060581023341442',
        'no_resi' => '000000002925',
        'no_tiket' => 'BS-2026080346778',
        'nominal_transaksi' => 400000,
        'biaya_admin' => 0,
        'status' => 'Done',
        'tgl_transaksi' => '2026-07-31',
        'tgl_terima' => '2026-08-03',
        'tgl_selesai' => '2026-08-04',
        'terminal_transaksi' => 'QRIS-MERCHANT-01',
        'keterangan_log' => 'Transaksi sukses pada sistem QRIS Jalin',
    ]);

    $response = $this->actingAs($admin)->get(route('jurnal.download', $jurnal->id));

    $response->assertStatus(200);
    // Memastikan view yang dipakai adalah cetak.qris
    $response->assertViewIs('cetak.qris');

    // Memastikan Halaman 1 (Form Penyelesaian Keluhan Nasabah) ada dalam dokumen cetak
    $response->assertSee('FORM PENYELESAIAN KELUHAN NASABAH');
    $response->assertSee('LEMBAR HELPDESK');
    $response->assertSee('TINDAK LANJUT PENYELESAIAN');

    // Memastikan Halaman 2 (Slip Jurnal QRIS Jalin) sesuai berkas PDF acuan
    $response->assertSee('NOTA DEBET');
    $response->assertSee('SLIP JURNAL');
    $response->assertSee('PENAMPUNGAN SELISIH QRIS JALIN');
    $response->assertSee('000002310711013360');
    $response->assertSee('SANTI');
    $response->assertSee('1030204003021');
    $response->assertSee('Rev. Transaksi QRIS');
    $response->assertSee('pemimpin Divisi');
    $response->assertSee('Pemimpin Bagian');
    $response->assertSee('Pemimpin Unit');
    $response->assertSee('Staf');
    $response->assertSee('MUJADID');
});

test('transaksi mobile banking non-QRIS tetap menggunakan template cetak.mbanking', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();

    $transaksiMbanking = MasterTransaksi::create([
        'jenis_transaksi' => 'TRANSFER ONLINE',
        'channel' => 'MOBILE BANKING',
        'biaya_admin' => 6500,
    ]);

    $jurnal = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksiMbanking->id,
        'nama_nasabah' => 'AHMAD RIFAI',
        'no_rekening' => '1030204009999',
        'no_kartu' => '6060581023349999',
        'no_resi' => '000000008888',
        'no_tiket' => 'BS-2026080349999',
        'nominal_transaksi' => 500000,
        'biaya_admin' => 6500,
        'status' => 'Done',
        'tgl_transaksi' => '2026-08-01',
        'tgl_terima' => '2026-08-02',
        'tgl_selesai' => '2026-08-03',
        'terminal_transaksi' => 'MB-APP',
        'keterangan_log' => 'Transfer berhasil',
    ]);

    $response = $this->actingAs($admin)->get(route('jurnal.download', $jurnal->id));

    $response->assertStatus(200);
    $response->assertViewIs('cetak.mbanking');
    $response->assertDontSee('PENAMPUNGAN SELISIH QRIS JALIN');
});

test('transaksi QRIS dengan parameter format=form menampilkan view form keluhan cetak.lokal', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();

    $transaksiQris = MasterTransaksi::create([
        'jenis_transaksi' => 'QRIS',
        'channel' => 'MOBILE BANKING',
        'biaya_admin' => 0,
    ]);

    $jurnal = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksiQris->id,
        'nama_nasabah' => 'SANTI',
        'no_rekening' => '1030204003021',
        'no_kartu' => '6060581023341442',
        'no_resi' => '000000002925',
        'no_tiket' => 'BS-2026080346778',
        'nominal_transaksi' => 400000,
        'biaya_admin' => 0,
        'status' => 'Done',
        'tgl_transaksi' => '2026-07-31',
        'tgl_terima' => '2026-08-03',
        'tgl_selesai' => '2026-08-04',
        'terminal_transaksi' => 'QRIS-MERCHANT-01',
        'keterangan_log' => 'Transaksi sukses pada sistem QRIS Jalin',
    ]);

    $response = $this->actingAs($admin)->get(route('jurnal.download', ['id' => $jurnal->id, 'format' => 'form']));

    $response->assertStatus(200);
    $response->assertViewIs('cetak.lokal');
    $response->assertSee('FORM PENYELESAIAN KELUHAN NASABAH');
});

