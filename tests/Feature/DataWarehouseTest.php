<?php

use App\Models\DimCabang;
use App\Models\DimTransaksi;
use App\Models\DimWaktu;
use App\Models\FactKeluhan;
use App\Models\Jurnal;
use App\Models\MasterCabang;
use App\Models\MasterTransaksi;
use App\Services\DataWarehouseEtlService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pipeline etl data warehouse berhasil memetakan skema bintang dan fakta keluhan', function () {
    // 1. Arrange: Buat data operasional
    $cabang = MasterCabang::create([
        'kode_cabang' => '001',
        'nama_cabang' => 'Cabang Utama Palu',
    ]);

    $transaksi = MasterTransaksi::create([
        'jenis_transaksi' => 'Tarik Tunai ATM',
        'channel'         => 'ATM',
    ]);

    $jurnal = Jurnal::create([
        'master_cabang_id'    => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'no_tiket'            => 'BS-20261101001',
        'no_resi'             => 'RESI12345',
        'nama_nasabah'        => 'Budi Santoso',
        'no_rekening'         => '1234567890',
        'nominal_transaksi'   => 500000,
        'biaya_admin'         => 2500,
        'status'              => 'Done',
        'tgl_transaksi'       => '2026-11-01',
        'tgl_terima'          => '2026-11-01',
        'tgl_selesai'         => '2026-11-03',
        'permasalahan'        => 'Uang tidak keluar tapi saldo terpotong',
    ]);

    // 2. Act: Jalankan ETL Service
    $service = app(DataWarehouseEtlService::class);
    $hasil = $service->jalankanEtl();

    // 3. Assert: Periksa hasil ETL
    expect($hasil['total_ekstrak'])->toBe(1);
    expect($hasil['total_fakta'])->toBe(1);

    // Assert dimensi waktu terbentuk
    $dimWaktu = DimWaktu::first();
    expect($dimWaktu)->not->toBeNull();
    expect($dimWaktu->tahun)->toBe(2026);
    expect($dimWaktu->kuartal)->toBe('Q4');
    expect($dimWaktu->nama_bulan)->toBe('November');

    // Assert dimensi cabang & transaksi
    expect(DimCabang::where('master_cabang_id', $cabang->id)->exists())->toBeTrue();
    expect(DimTransaksi::where('master_transaksi_id', $transaksi->id)->exists())->toBeTrue();

    // Assert tabel fakta
    $fakta = FactKeluhan::where('jurnal_id', $jurnal->id)->first();
    expect($fakta)->not->toBeNull();
    expect($fakta->status)->toBe('Done');
    expect($fakta->is_selesai)->toBeTrue();
    expect($fakta->nominal_transaksi)->toBe(500000.0);
    expect($fakta->biaya_admin)->toBe(2500.0);
    expect($fakta->durasi_penyelesaian_hari)->toBe(2);
});
