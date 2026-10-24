<?php

use App\Models\Jurnal;
use App\Models\Pengaduan;
use App\Services\NomorTiketService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Nomor tiket dari pengaduan CS cabang (dibuat sistem)
|--------------------------------------------------------------------------
*/

test('pengaduan cs memperoleh nomor tiket berformat BS-YYYYMMDD lima angka', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $pengaduan = buatPengaduan($cs, buatTransaksi());

    expect($pengaduan->nomor_tiket)->toMatch('/^BS-\d{13}$/')
        ->and($pengaduan->nomor_tiket)->toStartWith('BS-'.now()->format('Ymd'))
        ->and(strlen($pengaduan->nomor_tiket))->toBe(16)
        ->and(NomorTiketService::formatValid($pengaduan->nomor_tiket))->toBeTrue();
});

test('nomor tiket pengaduan selalu unik walau dibuat banyak pada hari yang sama', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $nomor = [];
    for ($i = 0; $i < 120; $i++) {
        $nomor[] = buatPengaduan($cs, $transaksi, ['no_resi' => 'R'.$i])->nomor_tiket;
    }

    expect($nomor)->toHaveCount(120)
        ->and(array_unique($nomor))->toHaveCount(120);
});

test('nomor baru tidak menabrak nomor yang sudah dipakai pengaduan maupun jurnal', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $terpakai = [];
    for ($i = 0; $i < 40; $i++) {
        $terpakai[] = buatPengaduan($cs, $transaksi, ['no_resi' => 'P'.$i])->nomor_tiket;
    }
    for ($i = 0; $i < 40; $i++) {
        $terpakai[] = Jurnal::create(dataJurnalDb($cabang, $transaksi, [
            'no_resi' => 'J'.$i,
            'no_tiket' => NomorTiketService::buat(),
        ]))->no_tiket;
    }

    for ($i = 0; $i < 30; $i++) {
        expect($terpakai)->not->toContain(NomorTiketService::buat());
    }

    expect(array_unique($terpakai))->toHaveCount(80);
});

test('jurnal dari pengaduan membawa nomor tiket pengaduan, bukan nomor baru', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $admin = buatAdmin();
    $transaksi = buatTransaksi();
    $pengaduan = buatPengaduan($cs, $transaksi);

    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi, [
        'pengaduan_id' => $pengaduan->id,
        'no_tiket' => 'COBA-GANTI-999',   // kiriman klien harus diabaikan
    ]));

    expect(Jurnal::first()->no_tiket)->toBe($pengaduan->nomor_tiket);
});

test('nomor tiket dari pengaduan tampil terkunci pada form jurnal', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $admin = buatAdmin();
    $pengaduan = buatPengaduan($cs, buatTransaksi());

    $form = $this->actingAs($admin)->get(route('jurnal.create', ['pengaduan' => $pengaduan->id]));

    $form->assertStatus(200);
    $form->assertSee($pengaduan->nomor_tiket);
    $form->assertSee('Dari pengaduan CS');
    $form->assertDontSee('name="no_tiket"', false);
});

/*
|--------------------------------------------------------------------------
| Nomor tiket jurnal yang diinput langsung Admin Pusat (diketik manual)
|--------------------------------------------------------------------------
| Berkas menempuh cabang → penyelia → Divisi Literasi sebelum sampai ke
| Divisi IT, sehingga Tanggal Terima Keluhan pada form bukan tanggal CS
| menerima keluhan. Nomor tiket karena itu tidak boleh dibuat dari tanggal
| tersebut, melainkan diketik sesuai berkas.
*/

test('jurnal tanpa pengaduan memakai nomor tiket yang diketik petugas', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi, [
        'no_tiket' => 'BS-2026071745807',
        'tgl_terima' => '2026-09-04',
    ]))->assertRedirect(route('jurnal.index'));

    // Nomor tetap seperti yang diketik, tidak diganti sistem dan tidak
    // mengikuti Tanggal Terima Keluhan pada form
    expect(Jurnal::first()->no_tiket)->toBe('BS-2026071745807');
});

test('nomor tiket di luar format baku tetap diterima apa adanya', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi, [
        'no_tiket' => 'PRO AKTIF',
    ]));

    expect(Jurnal::first()->no_tiket)->toBe('PRO AKTIF');
});

test('form jurnal biasa menyediakan isian nomor tiket yang dapat diketik', function () {
    $admin = buatAdmin();
    buatCabang();
    buatTransaksi();

    $form = $this->actingAs($admin)->get(route('jurnal.create'));

    $form->assertStatus(200);
    $form->assertSee('Nomor Tiket Keluhan');
    $form->assertSee('name="no_tiket"', false);
    $form->assertSee('Divisi Literasi');
});

test('nomor tiket jurnal biasa dapat dikoreksi lewat form edit', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi, ['no_tiket' => 'BS-2026090411111']));
    $jurnal = Jurnal::first();

    $halaman = $this->actingAs($admin)->get(route('jurnal.edit', $jurnal->id));
    $halaman->assertStatus(200)->assertSee('name="no_tiket"', false);

    $this->actingAs($admin)->put(route('jurnal.update', $jurnal->id), dataJurnal($cabang, $transaksi, [
        'no_tiket' => 'BS-2026090422222',
    ]))->assertRedirect(route('jurnal.index'));

    expect($jurnal->fresh()->no_tiket)->toBe('BS-2026090422222');
});

test('nomor tiket jurnal dari pengaduan tidak dapat diubah lewat form edit', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $admin = buatAdmin();
    $transaksi = buatTransaksi();
    $pengaduan = buatPengaduan($cs, $transaksi);

    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi, [
        'pengaduan_id' => $pengaduan->id,
    ]));
    $jurnal = Jurnal::first();

    $halaman = $this->actingAs($admin)->get(route('jurnal.edit', $jurnal->id));
    $halaman->assertStatus(200)->assertSee('Dari pengaduan CS')->assertDontSee('name="no_tiket"', false);

    $this->actingAs($admin)->put(route('jurnal.update', $jurnal->id), dataJurnal($cabang, $transaksi, [
        'no_tiket' => 'BS-2026090499999',
    ]));

    expect($jurnal->fresh()->no_tiket)->toBe($pengaduan->nomor_tiket);
});

test('validasi format nomor tiket menolak pola yang salah', function () {
    expect(NomorTiketService::formatValid('BS-2026090412345'))->toBeTrue()
        ->and(NomorTiketService::formatValid('BS-2026090400000'))->toBeTrue()
        ->and(NomorTiketService::formatValid('BS-202609041234'))->toBeFalse()
        ->and(NomorTiketService::formatValid('BS-20260904123456'))->toBeFalse()
        ->and(NomorTiketService::formatValid('BS-2026090412345 '))->toBeTrue()
        ->and(NomorTiketService::formatValid('XX-2026090412345'))->toBeFalse()
        ->and(NomorTiketService::formatValid('PRO AKTIF'))->toBeFalse()
        ->and(NomorTiketService::formatValid('-'))->toBeFalse()
        ->and(NomorTiketService::formatValid(null))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Perapian nomor tiket yang diketik petugas (spasi di sekitar tanda hubung)
|--------------------------------------------------------------------------
*/

test('nomor tiket berspasi dirapikan menjadi bentuk resmi', function () {
    expect(NomorTiketService::rapikan('BS - 2026081347674'))->toBe('BS-2026081347674')
        ->and(NomorTiketService::rapikan('BS- 2026081347674'))->toBe('BS-2026081347674')
        ->and(NomorTiketService::rapikan('BS -2026081347674'))->toBe('BS-2026081347674')
        ->and(NomorTiketService::rapikan('bs - 2026081347674'))->toBe('BS-2026081347674')
        ->and(NomorTiketService::rapikan('BS 2026081347674'))->toBe('BS-2026081347674')
        ->and(NomorTiketService::rapikan('  BS - 2026081347674  '))->toBe('BS-2026081347674')
        ->and(NomorTiketService::rapikan('BS-2026081347674'))->toBe('BS-2026081347674');
});

test('nomor tiket manual berupa teks bebas tidak ikut diubah', function () {
    // Jurnal input langsung memang menerima format apa pun; jangan sampai
    // perapian ini merusak nomor lama seperti "PRO AKTIF" (28 baris di produksi).
    expect(NomorTiketService::rapikan('PRO AKTIF'))->toBe('PRO AKTIF')
        ->and(NomorTiketService::rapikan('-'))->toBe('-')
        ->and(NomorTiketService::rapikan(''))->toBe('')
        ->and(NomorTiketService::rapikan(null))->toBe('')
        ->and(NomorTiketService::rapikan('PROAKTIF'))->toBe('PROAKTIF')
        ->and(NomorTiketService::rapikan('Manual 123'))->toBe('Manual 123')
        ->and(NomorTiketService::rapikan('KLAIM BS-2026'))->toBe('KLAIM BS-2026')
        ->and(NomorTiketService::rapikan('BS/2026-01'))->toBe('BS/2026-01');
});

test('menyimpan jurnal merapikan nomor tiket berspasi yang diketik petugas', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi, [
        'no_tiket' => 'BS - 2026081347674',
    ]))->assertRedirect(route('jurnal.index'));

    expect(Jurnal::first()->no_tiket)->toBe('BS-2026081347674');
});

test('memperbarui jurnal juga merapikan nomor tiket berspasi', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $jurnal = Jurnal::create(dataJurnalDb($cabang, $transaksi));

    $this->actingAs($admin)->put(route('jurnal.update', $jurnal->id), dataJurnal($cabang, $transaksi, [
        'no_tiket' => 'BS -  2026081847820',
    ]))->assertRedirect(route('jurnal.index'));

    expect($jurnal->fresh()->no_tiket)->toBe('BS-2026081847820');
});

test('nomor tiket manual tetap tersimpan utuh lewat form jurnal', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $this->actingAs($admin)->post(route('jurnal.store'), dataJurnal($cabang, $transaksi, [
        'no_tiket' => 'PRO AKTIF',
    ]))->assertRedirect(route('jurnal.index'));

    expect(Jurnal::first()->no_tiket)->toBe('PRO AKTIF');
});

test('penghapusan pengaduan tidak menyisakan nomor tiket kembar', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $pengaduan = buatPengaduan($cs, $transaksi);
    $nomor = $pengaduan->nomor_tiket;
    $pengaduan->delete();

    // Nomor bekas pengaduan yang dihapus boleh dipakai lagi, dan tetap unik
    expect(Pengaduan::where('nomor_tiket', $nomor)->exists())->toBeFalse()
        ->and(NomorTiketService::terpakai($nomor))->toBeFalse();
});
