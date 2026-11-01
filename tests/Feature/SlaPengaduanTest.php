<?php

use App\Enums\PengaduanStatus;
use App\Models\MasterCabang;
use App\Models\MasterTransaksi;
use App\Models\Pengaduan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pengaduan baru otomatis menghitung durasi sla dan batas jatuh tempo sesuai prioritas', function () {
    $cabang = MasterCabang::create(['kode_cabang' => '001', 'nama_cabang' => 'Cabang Utama Palu']);
    $transaksi = MasterTransaksi::create(['jenis_transaksi' => 'Tarik Tunai', 'channel' => 'ATM']);
    $cs = User::factory()->create(['role' => 'cs', 'master_cabang_id' => $cabang->id]);

    // Test 1: Prioritas Tinggi (2 Hari Kerja)
    $pengaduanTinggi = Pengaduan::create([
        'user_id' => $cs->id,
        'nama_pelapor' => 'CS Palu',
        'master_cabang_id' => $cabang->id,
        'kategori' => 'ATM',
        'prioritas' => 'Tinggi',
        'nama_nasabah' => 'Ahmad',
        'no_hp' => '08123456789',
        'no_ktp' => '7201010101010001',
        'no_rekening' => '111222333',
        'master_transaksi_id' => $transaksi->id,
        'no_resi' => 'RESI001',
        'channel' => 'ATM',
        'nominal_transaksi' => 1000000,
        'tgl_transaksi' => '2026-11-01',
        'kronologi' => 'Uang di mesin ATM tidak keluar tapi saldo terpotong',
    ]);

    expect($pengaduanTinggi->sla_hari)->toBe(2);
    expect($pengaduanTinggi->tgl_jatuh_tempo)->not->toBeNull();

    // Test 2: Prioritas Sedang (5 Hari Kerja)
    $pengaduanSedang = Pengaduan::create([
        'user_id' => $cs->id,
        'nama_pelapor' => 'CS Palu',
        'master_cabang_id' => $cabang->id,
        'kategori' => 'ATM',
        'prioritas' => 'Sedang',
        'nama_nasabah' => 'Budi',
        'no_hp' => '08123456788',
        'no_ktp' => '7201010101010002',
        'no_rekening' => '111222334',
        'master_transaksi_id' => $transaksi->id,
        'no_resi' => 'RESI002',
        'channel' => 'ATM',
        'nominal_transaksi' => 500000,
        'tgl_transaksi' => '2026-11-01',
        'kronologi' => 'Transfer ATM pending saldo berkurang belum masuk tujuan',
    ]);

    expect($pengaduanSedang->sla_hari)->toBe(5);

    // Test 3: Prioritas Rendah (14 Hari Kerja)
    $pengaduanRendah = Pengaduan::create([
        'user_id' => $cs->id,
        'nama_pelapor' => 'CS Palu',
        'master_cabang_id' => $cabang->id,
        'kategori' => 'ADMIN',
        'prioritas' => 'Rendah',
        'nama_nasabah' => 'Citra',
        'no_hp' => '08123456787',
        'no_ktp' => '7201010101010003',
        'no_rekening' => '111222335',
        'master_transaksi_id' => $transaksi->id,
        'no_resi' => 'RESI003',
        'channel' => 'TELLER',
        'nominal_transaksi' => 0,
        'tgl_transaksi' => '2026-11-01',
        'kronologi' => 'Koreksi penulisan nama di buku tabungan nasabah',
    ]);

    expect($pengaduanRendah->sla_hari)->toBe(14);
});

test('status sla mendeteksi kasus overdue yang lewat batas tanggal jatuh tempo', function () {
    $cabang = MasterCabang::create(['kode_cabang' => '001', 'nama_cabang' => 'Cabang Utama Palu']);
    $transaksi = MasterTransaksi::create(['jenis_transaksi' => 'Tarik Tunai', 'channel' => 'ATM']);
    $cs = User::factory()->create(['role' => 'cs', 'master_cabang_id' => $cabang->id]);

    $pengaduan = Pengaduan::create([
        'user_id' => $cs->id,
        'nama_pelapor' => 'CS Palu',
        'master_cabang_id' => $cabang->id,
        'kategori' => 'ATM',
        'prioritas' => 'Tinggi',
        'nama_nasabah' => 'Dewi',
        'no_hp' => '08123456786',
        'no_ktp' => '7201010101010004',
        'no_rekening' => '111222336',
        'master_transaksi_id' => $transaksi->id,
        'no_resi' => 'RESI004',
        'channel' => 'ATM',
        'nominal_transaksi' => 200000,
        'tgl_transaksi' => '2026-10-01',
        'kronologi' => 'Keluhan masa lalu yang belum diselesaikan admin',
    ]);

    // Simulasikan tanggal jatuh tempo sudah lewat 5 hari lalu
    $pengaduan->tgl_jatuh_tempo = now()->subDays(5)->toDateString();
    $pengaduan->saveQuietly();

    expect($pengaduan->statusSla())->toBe('overdue');
    $lencana = $pengaduan->lencanaSla();
    expect($lencana['status'])->toBe('overdue');
    expect($lencana['label'])->toContain('Lewat SLA');
});
