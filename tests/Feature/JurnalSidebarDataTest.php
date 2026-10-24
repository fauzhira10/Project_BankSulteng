<?php

use App\Models\MasterCabang;
use App\Models\MasterTransaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('halaman formulir jurnal keluhan dapat diakses dan memuat sidebar', function () {
    $response = $this->actingAs(buatAdmin())->get('/jurnal/input');

    $response->assertStatus(200);
    $response->assertSee('E-JURNAL KELUHAN');
    $response->assertSee('Input Jurnal Keluhan');
    $response->assertSee('Data Keluhan');
    $response->assertSee('Formulir Pengaduan & Jurnal Transaksi', false);
});

test('halaman data keluhan dapat diakses dan menampilkan filter serta statistik', function () {
    $response = $this->actingAs(buatAdmin())->get('/jurnal/data');

    $response->assertStatus(200);
    $response->assertSee('E-JURNAL KELUHAN');
    $response->assertSee('Data Jurnal Keluhan');
    $response->assertSee('Pencarian & Filter Data Keluhan', false);
    $response->assertSee('Menunggu');
    $response->assertSee('Success');
    $response->assertSee('Done');
    $response->assertSee('Rejected');
});

test('api auto-fill transaksi mengembalikan biaya admin dan channel', function () {
    $cabang = MasterCabang::create([
        'kode_cabang' => '001',
        'nama_cabang' => 'Cabang Utama Palu',
    ]);

    $transaksi = MasterTransaksi::create([
        'jenis_transaksi' => 'ATM_TARIK TUNAI ATM BANK SULTENG',
        'channel' => 'ATM LOKAL',
        'biaya_admin' => 0,
    ]);

    $response = $this->actingAs(buatAdmin())->getJson("/api/transaksi/{$transaksi->id}");

    $response->assertStatus(200);
    $response->assertJson([
        'id' => $transaksi->id,
        'channel' => 'ATM LOKAL',
        'biaya_admin' => 0,
    ]);
});

test('dapat menyimpan jurnal keluhan baru dan tertera pada halaman data keluhan', function () {
    $admin = buatAdmin();

    $cabang = MasterCabang::create([
        'kode_cabang' => '002',
        'nama_cabang' => 'KCP Tinombo',
    ]);

    $transaksi = MasterTransaksi::create([
        'jenis_transaksi' => 'ATM_TARIK TUNAI DI BANK LAIN',
        'channel' => 'ATM BERSAMA',
        'biaya_admin' => 7500,
    ]);

    $postData = [
        'nama_nasabah' => 'Ahmad Rifai',
        'no_resi' => '12345678',
        'no_rekening' => '001099887766',
        'no_kartu' => '6019001234567890',
        'no_tiket' => 'TKT-2026-TEST',
        'tgl_terima' => '2026-08-20',
        'tgl_transaksi' => '2026-08-19',
        'tgl_selesai' => '2026-08-21',
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'terminal_transaksi' => 'ATM-TINOMBO-01',
        'nominal_transaksi' => 500000,
        'keterangan_log' => 'Uang tidak keluar tapi saldo terdebet',
        'status' => 'Menunggu',
    ];

    $response = $this->actingAs($admin)->post('/jurnal/simpan', $postData);

    $response->assertRedirect('/jurnal/data');
    $response->assertSessionHas('success');

    // Cek di halaman data keluhan
    $dataResponse = $this->actingAs($admin)->get('/jurnal/data?q=12345678');
    $dataResponse->assertStatus(200);
    $dataResponse->assertSee('AHMAD RIFAI');
    $dataResponse->assertSee('12345678');
    $dataResponse->assertSee('500.000');
});
