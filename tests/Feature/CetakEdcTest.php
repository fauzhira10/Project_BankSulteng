<?php

use App\Models\Jurnal;
use App\Models\MasterCabang;
use App\Models\MasterTransaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('transaksi EDC Bank Lain menggunakan template cetak.atmb (Form Penyelesaian + Slip ATM Bersama)', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();

    $transaksiEdcBankLain = MasterTransaksi::create([
        'jenis_transaksi' => 'EDC BANK LAIN',
        'channel' => 'EDC BANK LAIN',
        'biaya_admin' => 0,
    ]);

    $jurnal = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksiEdcBankLain->id,
        'nama_nasabah' => 'HERMANTO',
        'no_rekening' => '1010203040506',
        'no_kartu' => '6060582001122334',
        'no_resi' => '000000001234',
        'no_tiket' => 'BS-202609080001',
        'nominal_transaksi' => 750000,
        'biaya_admin' => 0,
        'status' => 'Done',
        'tgl_transaksi' => '2026-09-05',
        'tgl_terima' => '2026-09-06',
        'tgl_selesai' => '2026-09-07',
        'terminal_transaksi' => 'EDC-MANDIRI-01',
        'keterangan_log' => 'Klaim diterima transaksi gagal di EDC Bank Lain',
    ]);

    $response = $this->actingAs($admin)->get(route('jurnal.download', $jurnal->id));

    $response->assertStatus(200);
    // Memastikan view yang dipakai adalah cetak.atmb (Form Penyelesaian + Slip ATM Bersama)
    $response->assertViewIs('cetak.atmb');

    // Memastikan Halaman 1 (Form Penyelesaian Keluhan Nasabah) ada
    $response->assertSee('FORM PENYELESAIAN KELUHAN NASABAH');
    $response->assertSee('LEMBAR HELPDESK');
    $response->assertSee('TINDAK LANJUT PENYELESAIAN');
    $response->assertSee('EDC BANK LAIN');

    // Memastikan Halaman 2 (Slip Jurnal ATM Bersama) ada
    $response->assertSee('NOTA DEBET');
    $response->assertSee('SLIP JURNAL');
    $response->assertSee('PENAMPUNGAN SELISIH ATM BERSAMA');
    $response->assertSee('HERMANTO');
    $response->assertSee('1010203040506');
});

test('transaksi EDC Bank Sulteng langsung menggunakan template cetak.edc (hanya slip EDC biasa)', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();

    $transaksiEdcSulteng = MasterTransaksi::create([
        'jenis_transaksi' => 'EDC',
        'channel' => 'DEBIT',
        'biaya_admin' => 0,
    ]);

    $jurnal = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksiEdcSulteng->id,
        'nama_nasabah' => 'NURHAYATI',
        'no_rekening' => '1020304050607',
        'no_kartu' => '6060583002233445',
        'no_resi' => '000000005678',
        'no_tiket' => 'BS-202609080002',
        'nominal_transaksi' => 250000,
        'biaya_admin' => 0,
        'status' => 'Done',
        'tgl_transaksi' => '2026-09-06',
        'tgl_terima' => '2026-09-07',
        'tgl_selesai' => '2026-09-08',
        'terminal_transaksi' => 'EDC-SULTENG-KCP-01',
        'keterangan_log' => 'Transaksi sukses diselesaikan pada mesin EDC Bank Sulteng',
    ]);

    $response = $this->actingAs($admin)->get(route('jurnal.download', $jurnal->id));

    $response->assertStatus(200);
    // Memastikan view yang dipakai adalah cetak.edc
    $response->assertViewIs('cetak.edc');

    // Memastikan TIDAK ada Form Penyelesaian Keluhan Nasabah (hanya slip biasa)
    $response->assertDontSee('FORM PENYELESAIAN KELUHAN NASABAH');
    $response->assertDontSee('LEMBAR HELPDESK');

    // Memastikan Slip EDC Bank Sulteng tampil lengkap
    $response->assertSee('NOTA DEBET');
    $response->assertSee('SLIP JURNAL');
    $response->assertSee('KEWAJIBAN PURCHASE & VOID MESIN EDC ATMB', false);
    $response->assertSee('000.00.2310713.001.360');
    $response->assertSee('NURHAYATI');
    $response->assertSee('1020304050607');
    $response->assertSee('Rev. Transaksi EDC');
});
