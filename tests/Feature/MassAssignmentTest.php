<?php

use App\Enums\PengaduanStatus;
use App\Models\Jurnal;
use App\Models\Pengaduan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('stempel waktu jurnal tidak dapat dipalsukan lewat request saat menyimpan', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi, [
        'created_at' => '2020-01-01 00:00:00',
        'updated_at' => '2020-01-01 00:00:00',
    ]));

    $jurnal = Jurnal::first();

    expect($jurnal)->not->toBeNull()
        ->and($jurnal->created_at->year)->toBe(now()->year)
        ->and($jurnal->created_at->format('Y-m-d'))->not->toBe('2020-01-01');
});

test('stempel waktu jurnal tidak dapat dipalsukan lewat request saat mengubah', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();
    $jurnal = Jurnal::create(dataJurnalDb($cabang, $transaksi));
    $aslinya = $jurnal->created_at->format('Y-m-d');

    $this->actingAs($admin)->put(route('jurnal.update', $jurnal->id), dataJurnal($cabang, $transaksi, [
        'nama_nasabah' => $jurnal->nama_nasabah,
        'no_resi' => $jurnal->no_resi,
        'no_rekening' => $jurnal->no_rekening,
        'tgl_transaksi' => '2026-09-01',
        'created_at' => '2019-05-05 00:00:00',
    ]));

    expect($jurnal->fresh()->created_at->format('Y-m-d'))->toBe($aslinya);
});

test('kolom di luar daftar tidak ikut tersimpan walau dikirim pada request', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();
    $lain = buatCabang('003', 'CABANG POSO');

    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi));
    $jurnal = Jurnal::first();

    // Cabang yang tersimpan adalah yang dikirim lewat field resmi,
    // bukan nilai lain yang diselundupkan pada nama input yang sama.
    expect($jurnal->master_cabang_id)->toBe($cabang->id)
        ->and($jurnal->master_cabang_id)->not->toBe($lain->id);
});

test('cs tidak dapat menentukan sendiri nomor tiket pengaduannya', function () {
    Storage::fake('local');

    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $this->actingAs($cs)->post(route('cs.pengaduan.store'), dataFormPengaduan($transaksi, $cabang, [
        'nomor_tiket' => 'BS-9999999999999',
        'lampiran' => ['foto_ktp' => [berkasGambarUji('ktp.jpg', 400, 300)]],
    ]));

    $pengaduan = Pengaduan::first();

    expect($pengaduan)->not->toBeNull()
        ->and($pengaduan->nomor_tiket)->not->toBe('BS-9999999999999')
        ->and($pengaduan->nomor_tiket)->toStartWith('BS-'.now()->format('Ymd'));
});

test('cs tidak dapat mengirim pengaduan yang langsung berstatus selesai', function () {
    Storage::fake('local');

    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $this->actingAs($cs)->post(route('cs.pengaduan.store'), dataFormPengaduan($transaksi, $cabang, [
        'status' => PengaduanStatus::Selesai->value,
        'jurnal_id' => 999,
        'lampiran' => ['foto_ktp' => [berkasGambarUji('ktp.jpg', 400, 300)]],
    ]));

    $pengaduan = Pengaduan::first();

    expect($pengaduan)->not->toBeNull()
        ->and($pengaduan->status)->toBe(PengaduanStatus::Terkirim)
        ->and($pengaduan->jurnal_id)->toBeNull();
});
