<?php

use App\Models\Jurnal;
use App\Support\Penyamaran;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('nomor disamarkan menyisakan empat digit terakhir', function () {
    expect(Penyamaran::nomor('00900001234'))->toBe('••••1234')
        ->and(Penyamaran::nomor('4213 5566 7788 9900'))->toBe('••••9900');
});

test('nomor pendek disamarkan seluruhnya', function () {
    // Menyisakan empat digit terakhir dari nomor lima digit sama saja dengan
    // menampilkannya utuh.
    expect(Penyamaran::nomor('12345'))->toBe('••••')
        ->and(Penyamaran::nomor('99'))->toBe('••••');
});

test('nilai kosong tetap tampil sebagai strip', function () {
    expect(Penyamaran::nomor(null))->toBe('-')
        ->and(Penyamaran::nomor(''))->toBe('-')
        ->and(Penyamaran::nomor('  '))->toBe('-')
        ->and(Penyamaran::nomor('-'))->toBe('-');
});

test('daftar data keluhan tidak memuat nomor rekening lengkap di mana pun', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    Jurnal::create(dataJurnalDb($cabang, $transaksi, [
        'no_rekening' => '00900001234',
        'no_kartu' => '4213556677889900',
    ]));

    $halaman = $this->actingAs($admin)->get(route('jurnal.index'));

    // Bukan hanya kolom yang tampak: nomor lengkap juga tidak boleh tersisa di
    // atribut data-search maupun muatan tombol aksi.
    $halaman->assertOk()
        ->assertSee('••••1234')
        ->assertDontSee('00900001234')
        ->assertDontSee('4213556677889900');
});

test('bekal tombol tabel tidak membawa nomor rekening dan kartu', function () {
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $jurnal = Jurnal::create(dataJurnalDb($cabang, $transaksi, [
        'no_rekening' => '00900001234',
        'no_kartu' => '4213556677889900',
    ]));

    $bekal = $jurnal->bekalTombol();

    expect($bekal)->not->toHaveKey('no_rekening')
        ->and($bekal)->not->toHaveKey('no_kartu')
        ->and($bekal['id'])->toBe($jurnal->id)
        ->and($bekal['master_transaksi']['channel'])->toBe($transaksi->channel);
});

test('rincian satu jurnal tetap memuat nomor lengkap bagi admin', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();

    $jurnal = Jurnal::create(dataJurnalDb($cabang, $transaksi, [
        'no_rekening' => '00900001234',
        'no_kartu' => '4213556677889900',
    ]));

    // Modal rincian mengambil datanya dari sini, satu baris pada satu waktu.
    $this->actingAs($admin)->getJson(route('api.jurnal.detail', $jurnal->id))
        ->assertOk()
        ->assertJsonPath('no_rekening', '00900001234')
        ->assertJsonPath('no_kartu', '4213556677889900');
});

test('daftar pengaduan admin dan cs menyamarkan nomor rekening', function () {
    $cabang = buatCabang();
    $admin = buatAdmin();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    buatPengaduan($cs, $transaksi, ['no_rekening' => '00900001234']);

    $this->actingAs($admin)->get(route('admin.pengaduan.index'))
        ->assertOk()
        ->assertSee('••••1234')
        ->assertDontSee('00900001234');

    $this->actingAs($cs)->get(route('cs.pengaduan.index'))
        ->assertOk()
        ->assertSee('••••1234')
        ->assertDontSee('00900001234');

    $this->actingAs($cs)->get(route('cs.dashboard'))
        ->assertOk()
        ->assertSee('••••1234')
        ->assertDontSee('00900001234');
});

test('rincian pengaduan tetap menampilkan nomor lengkap', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    // Petugas yang sedang menangani satu pengaduan perlu nomor utuh untuk
    // dicocokkan dengan lampiran KTP nasabah.
    $pengaduan = buatPengaduan($cs, $transaksi, ['no_rekening' => '00900001234']);

    $this->actingAs($cs)->get(route('cs.pengaduan.show', $pengaduan))
        ->assertOk()
        ->assertSee('00900001234');
});

test('panel keluhan berulang hanya menanam id, bukan seluruh baris', function () {
    $cabang = buatCabang();
    $admin = buatAdmin();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $jurnal = Jurnal::create(dataJurnalDb($cabang, $transaksi, [
        'nama_nasabah' => 'BUDI SANTOSO',
        'no_resi' => '778899',
        'tgl_transaksi' => '2026-09-01',
        'no_rekening' => '00900001234',
        'no_kartu' => '4213556677889900',
    ]));

    $pengaduan = buatPengaduan($cs, $transaksi, [
        'nama_nasabah' => 'BUDI SANTOSO',
        'no_resi' => '778899',
        'tgl_transaksi' => '2026-10-05',
        'no_rekening' => '001099887766',
        'no_ktp' => '7271012345670001',
    ]);

    $html = $this->actingAs($admin)->getJson(route('api.duplikat.periksa', [
        'nama' => 'budi santoso', 'resi' => '778899', 'tgl' => '2026-11-01',
    ]))->assertOk()->json('html');

    expect($html)->toContain('data-jurnal-id="'.$jurnal->id.'"')
        ->and($html)->toContain('data-pengaduan-id="'.$pengaduan->id.'"')
        ->and($html)->not->toContain('00900001234')
        ->and($html)->not->toContain('4213556677889900')
        ->and($html)->not->toContain('001099887766')
        ->and($html)->not->toContain('7271012345670001');
});

test('rincian pengaduan tersedia lewat api bagi admin', function () {
    $cabang = buatCabang();
    $admin = buatAdmin();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $pengaduan = buatPengaduan($cs, $transaksi, ['no_rekening' => '001099887766']);

    $this->actingAs($admin)->getJson(route('api.pengaduan.detail', $pengaduan))
        ->assertOk()
        ->assertJsonPath('no_rekening', '001099887766')
        ->assertJsonPath('cabang.kode_cabang', $cabang->kode_cabang);
});

test('cs tidak dapat memakai endpoint rincian pengaduan milik admin', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $pengaduan = buatPengaduan($cs, $transaksi);

    // Endpoint ini berada di dalam grup role:admin — CS diarahkan ke berandanya
    // sendiri, bukan diberi datanya.
    $this->actingAs($cs)->get(route('api.pengaduan.detail', $pengaduan))
        ->assertRedirect(route('cs.dashboard'));
});
