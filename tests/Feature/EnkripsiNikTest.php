<?php

use App\Models\Pengaduan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('nik tersimpan terenkripsi di basis data', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $pengaduan = buatPengaduan($cs, $transaksi, ['no_ktp' => '7271012345670001']);

    $mentah = DB::table('pengaduans')->where('id', $pengaduan->id)->value('no_ktp');

    // Salinan basis data yang bocor tidak boleh langsung menjadi daftar NIK.
    expect($mentah)->not->toBe('7271012345670001')
        ->and($mentah)->not->toContain('7271012345670001')
        ->and(Crypt::decryptString($mentah))->toBe('7271012345670001');
});

test('nik terbaca utuh lewat model dan panel rincian', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $pengaduan = buatPengaduan($cs, $transaksi, ['no_ktp' => '7271012345670001']);

    // Petugas yang menangani satu pengaduan tetap melihat NIK utuh untuk
    // dicocokkan dengan lampiran KTP nasabah.
    expect($pengaduan->fresh()->no_ktp)->toBe('7271012345670001');

    $this->actingAs($cs)->get(route('cs.pengaduan.show', $pengaduan))
        ->assertOk()
        ->assertSee('7271012345670001');
});

test('cs dapat mengirim pengaduan baru dengan nik terenkripsi', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    Storage::fake('local');

    $this->actingAs($cs)->post(route('cs.pengaduan.store'), dataFormPengaduan($transaksi, $cabang, [
        'no_ktp' => '7371019988770002',
        'lampiran' => ['foto_ktp' => [UploadedFile::fake()->image('ktp.jpg', 320, 240)]],
    ]))->assertRedirect();

    $pengaduan = Pengaduan::latest('id')->firstOrFail();

    expect($pengaduan->no_ktp)->toBe('7371019988770002')
        ->and(DB::table('pengaduans')->where('id', $pengaduan->id)->value('no_ktp'))
        ->not->toBe('7371019988770002');
});

test('pencarian pengaduan tetap bekerja untuk kolom yang tidak dienkripsi', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $pengaduan = buatPengaduan($cs, $transaksi, [
        'nama_nasabah' => 'BUDI SANTOSO',
        'no_hp' => '081234567890',
        'no_rekening' => '00900001234',
        'no_ktp' => '7271012345670001',
    ]);

    // no_hp sengaja TIDAK ikut dienkripsi: kolom ini dipakai menelusuri
    // pengaduan saat nasabah menelepon menyusul laporannya.
    expect(Pengaduan::cari('081234567890')->pluck('id'))->toContain($pengaduan->id)
        ->and(Pengaduan::cari('BUDI SANTOSO')->pluck('id'))->toContain($pengaduan->id)
        ->and(Pengaduan::cari('00900001234')->pluck('id'))->toContain($pengaduan->id);
});

test('migrasi mengenkripsi baris lama dan aman dijalankan ulang', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $pengaduan = buatPengaduan($cs, $transaksi);

    // Tiru baris peninggalan sebelum enkripsi: ditulis langsung, melewati cast.
    DB::table('pengaduans')->where('id', $pengaduan->id)->update(['no_ktp' => '7271019999880003']);

    $migrasi = require database_path('migrations/2026_09_14_000001_enkripsi_no_ktp_pengaduans.php');
    $migrasi->up();

    expect($pengaduan->fresh()->no_ktp)->toBe('7271019999880003');

    // Dijalankan ulang, baris yang sudah terenkripsi tidak boleh dienkripsi
    // dua kali — itu akan membuatnya tidak terbaca lagi.
    $migrasi->up();

    expect($pengaduan->fresh()->no_ktp)->toBe('7271019999880003');
});

test('rollback migrasi mengembalikan nik ke bentuk semula', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $pengaduan = buatPengaduan($cs, $transaksi, ['no_ktp' => '7271012345670001']);

    $migrasi = require database_path('migrations/2026_09_14_000001_enkripsi_no_ktp_pengaduans.php');
    $migrasi->down();

    // Jalan keluar bila enkripsi hendak dibatalkan: nilainya kembali terbaca polos.
    expect(DB::table('pengaduans')->where('id', $pengaduan->id)->value('no_ktp'))
        ->toBe('7271012345670001');
});
