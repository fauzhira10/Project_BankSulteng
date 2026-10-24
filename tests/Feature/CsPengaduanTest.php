<?php

use App\Enums\PengaduanStatus;
use App\Models\Jurnal;
use App\Models\Pengaduan;
use App\Models\PengaduanLampiran;
use App\Services\LampiranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('cs dapat mengirim pengaduan dan lampiran disimpan sesuai format aslinya', function () {
    Storage::fake('local');

    $cabang = buatCabang('001', 'CABANG UTAMA');
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $data = dataFormPengaduan($transaksi, $cabang, [
        'lampiran' => [
            'foto_ktp' => [
                UploadedFile::fake()->image('ktp-depan.jpg', 640, 480),
                UploadedFile::fake()->image('ktp-belakang.png', 480, 640),
            ],
            'form_keluhan' => [
                UploadedFile::fake()->create('form-keluhan.pdf', 120, 'application/pdf'),
            ],
        ],
    ]);

    $response = $this->actingAs($cs)->post(route('cs.pengaduan.store'), $data);

    $pengaduan = Pengaduan::first();
    expect($pengaduan)->not->toBeNull();

    $response->assertRedirect(route('cs.pengaduan.show', $pengaduan));
    $response->assertSessionHas('success');

    expect($pengaduan->master_cabang_id)->toBe($cabang->id)
        ->and($pengaduan->user_id)->toBe($cs->id)
        ->and($pengaduan->status)->toBe(PengaduanStatus::Terkirim)
        ->and($pengaduan->nama_nasabah)->toBe('AHMAD RIFAI')
        ->and($pengaduan->kategori)->toBe('TRANSAKSI ATM')
        ->and((float) $pengaduan->nominal_transaksi)->toBe(1500000.0)
        ->and($pengaduan->nomor_tiket)->toMatch('/^BS-\d{13}$/')
        ->and($pengaduan->nomor_tiket)->toStartWith('BS-'.now()->format('Ymd'));

    // Satu baris per berkas — tidak digabung dan tidak dikonversi
    expect($pengaduan->lampirans)->toHaveCount(3);

    $ktp = PengaduanLampiran::where('jenis', 'foto_ktp')->orderBy('id')->get();
    expect($ktp)->toHaveCount(2)
        ->and($ktp[0]->nama_asli)->toBe('ktp-depan.jpg')
        ->and($ktp[0]->path)->toEndWith('.jpg')
        ->and($ktp[0]->mime)->toBe('image/jpeg')
        ->and($ktp[0]->sumber)->toBe('gambar')
        ->and($ktp[0]->adalahGambar())->toBeTrue()
        ->and($ktp[0]->labelFormat())->toBe('JPG')
        ->and($ktp[1]->nama_asli)->toBe('ktp-belakang.png')
        ->and($ktp[1]->path)->toEndWith('.png')
        ->and($ktp[1]->mime)->toBe('image/png');

    foreach ($ktp as $l) {
        Storage::disk('local')->assertExists($l->path);
    }

    // Berkas gambar tetap gambar (bukan PDF)
    expect(substr(Storage::disk('local')->get($ktp[0]->path), 0, 4))->not->toBe('%PDF');

    $form = PengaduanLampiran::where('jenis', 'form_keluhan')->first();
    expect($form->nama_asli)->toBe('form-keluhan.pdf')
        ->and($form->path)->toEndWith('.pdf')
        ->and($form->mime)->toBe('application/pdf')
        ->and($form->sumber)->toBe('pdf')
        ->and($form->adalahGambar())->toBeFalse();
    Storage::disk('local')->assertExists($form->path);
});

test('lampiran gambar disajikan dengan tipe mime aslinya dan isi berkas tidak berubah', function () {
    Storage::fake('local');

    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $gambar = UploadedFile::fake()->image('ktp.jpg', 320, 240);
    $isiAsli = file_get_contents($gambar->getRealPath());

    $this->actingAs($cs)->post(route('cs.pengaduan.store'), dataFormPengaduan($transaksi, $cabang, [
        'lampiran' => ['foto_ktp' => [$gambar]],
    ]));

    $pengaduan = Pengaduan::first();
    $lampiran = $pengaduan->lampirans()->first();

    // Byte berkas identik dengan yang dikirim CS
    expect(Storage::disk('local')->get($lampiran->path))->toBe($isiAsli);

    $response = $this->actingAs($cs)->get(route('pengaduan.lampiran.show', [$pengaduan, $lampiran]));

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'image/jpeg');
    expect($response->headers->get('content-disposition'))->toContain('.jpg');
});

test('foto beresolusi besar diperkecil namun formatnya tetap sama', function () {
    Storage::fake('local');

    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $besar = berkasGambarUji('ktp-besar.jpg', 4000, 3000);
    $ukuranAsli = filesize($besar->getRealPath());

    $this->actingAs($cs)->post(route('cs.pengaduan.store'), dataFormPengaduan($transaksi, $cabang, [
        'lampiran' => ['foto_ktp' => [$besar]],
    ]));

    $lampiran = Pengaduan::first()->lampirans()->first();
    $isi = Storage::disk('local')->get($lampiran->path);
    [$lebar, $tinggi, $tipe] = getimagesizefromstring($isi);

    $maks = (int) config('pengaduan.lebar_maks_gambar');

    // Diperkecil ke batas konfigurasi, rasio dipertahankan, tetap JPEG
    expect(max($lebar, $tinggi))->toBe($maks)
        ->and($lebar)->toBeGreaterThan($tinggi)
        ->and($tipe)->toBe(IMAGETYPE_JPEG)
        ->and($lampiran->path)->toEndWith('.jpg')
        ->and($lampiran->mime)->toBe('image/jpeg')
        ->and($lampiran->ukuran)->toBe(strlen($isi))
        ->and($lampiran->ukuran)->toBeLessThan($ukuranAsli);
});

test('png tetap png dan tidak pernah membengkak setelah dioptimalkan', function () {
    Storage::fake('local');

    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    // PNG berblok warna datar justru membesar bila diperkecil (interpolasi merusak
    // area datar), sehingga berkas asli yang lebih kecil harus dipertahankan.
    $png = berkasGambarUji('struk.png', 2600, 2600);
    $isiAsli = file_get_contents($png->getRealPath());

    $this->actingAs($cs)->post(route('cs.pengaduan.store'), dataFormPengaduan($transaksi, $cabang, [
        'lampiran' => ['foto_ktp' => [$png]],
    ]));

    $lampiran = Pengaduan::first()->lampirans()->first();
    $tersimpan = Storage::disk('local')->get($lampiran->path);
    [, , $tipe] = getimagesizefromstring($tersimpan);

    expect($tipe)->toBe(IMAGETYPE_PNG)
        ->and($lampiran->mime)->toBe('image/png')
        ->and($lampiran->path)->toEndWith('.png')
        ->and(strlen($tersimpan))->toBeLessThanOrEqual(strlen($isiAsli))
        ->and($tersimpan)->toBe($isiAsli);
});

test('png tanpa transparansi tidak diberi kanal alpha saat diperkecil', function () {
    // Menambah kanal alpha pada PNG tanpa transparansi membuat berkas membengkak
    // tanpa manfaat, jadi color type hasil olahan harus tetap RGB (2).
    $service = new ReflectionClass(LampiranService::class);
    $instance = $service->newInstance();

    $perkecil = $service->getMethod('perkecil');
    $encode = $service->getMethod('encode');
    $punyaAlpha = $service->getMethod('punyaAlpha');

    $sumber = berkasGambarUji('rgb.png', 2600, 2600);
    $isi = file_get_contents($sumber->getRealPath());

    expect($punyaAlpha->invoke($instance, $isi, 'png'))->toBeFalse();

    $img = imagecreatefromstring($isi);
    $kecil = $perkecil->invoke($instance, $img, 2000, false);
    $hasil = $encode->invoke($instance, $kecil, 'png');
    imagedestroy($kecil);

    expect(ord($hasil[25]))->toBe(2);          // color type 2 = RGB tanpa alpha
    expect(getimagesizefromstring($hasil)[0])->toBe(2000);
});

test('berkas pdf tidak pernah diubah isinya', function () {
    Storage::fake('local');

    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $pdf = UploadedFile::fake()->create('form-keluhan.pdf', 200, 'application/pdf');
    $isiAsli = file_get_contents($pdf->getRealPath());

    $this->actingAs($cs)->post(route('cs.pengaduan.store'), dataFormPengaduan($transaksi, $cabang, [
        'lampiran' => [
            'foto_ktp' => [UploadedFile::fake()->image('ktp.jpg', 320, 240)],
            'form_keluhan' => [$pdf],
        ],
    ]));

    $lampiran = Pengaduan::first()->lampirans()->where('jenis', 'form_keluhan')->first();

    expect(Storage::disk('local')->get($lampiran->path))->toBe($isiAsli)
        ->and($lampiran->mime)->toBe('application/pdf');
});

test('semua jenis lampiran dapat dibuka meski labelnya mengandung karakter tidak sah', function () {
    Storage::fake('local');

    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $pengaduan = buatPengaduan($cs, buatTransaksi());

    // "Kartu ATM / Debit" mengandung garis miring yang ditolak header Content-Disposition
    foreach (array_keys(config('pengaduan.jenis_lampiran')) as $jenis) {
        $lampiran = buatLampiran($pengaduan, $jenis, 'berkas.jpg', 'image/jpeg');

        $this->actingAs($cs)
            ->get(route('pengaduan.lampiran.show', [$pengaduan, $lampiran]))
            ->assertStatus(200)
            ->assertHeader('content-type', 'image/jpeg');

        expect($lampiran->namaUnduhan())
            ->not->toContain('/')
            ->not->toContain('\\')
            ->toEndWith('.jpg');
    }
});

test('asal cabang dipilih manual dan wajib diisi', function () {
    Storage::fake('local');
    $cabang = buatCabang('001', 'CABANG UTAMA');
    $cabangLain = buatCabang('003', 'CABANG POSO');
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();
    $ktp = ['lampiran' => ['foto_ktp' => [UploadedFile::fake()->image('ktp.jpg', 320, 240)]]];

    // Form menampilkan dropdown cabang dengan cabang akun CS terpilih sebagai default
    $form = $this->actingAs($cs)->get(route('cs.pengaduan.create'));
    $form->assertStatus(200);
    $form->assertSee('name="master_cabang_id"', false);
    $form->assertSee('CABANG POSO');

    // Tanpa asal cabang → ditolak
    $this->actingAs($cs)->from(route('cs.pengaduan.create'))
        ->post(route('cs.pengaduan.store'), dataFormPengaduan($transaksi, null, $ktp))
        ->assertSessionHasErrors(['master_cabang_id']);

    // Memilih cabang lain → tersimpan sesuai pilihan, nomor memakai kode cabang asal, CS pengirim tetap bisa melihat
    $this->actingAs($cs)->post(route('cs.pengaduan.store'), dataFormPengaduan($transaksi, $cabangLain, $ktp));

    $pengaduan = Pengaduan::first();
    expect($pengaduan->master_cabang_id)->toBe($cabangLain->id)
        // Nomor tiket tidak lagi memuat kode cabang, hanya tanggal kirim + angka acak
        ->and($pengaduan->nomor_tiket)->toStartWith('BS-'.now()->format('Ymd'));

    $this->actingAs($cs)->get(route('cs.pengaduan.show', $pengaduan))->assertStatus(200);
    $this->actingAs($cs)->get(route('cs.pengaduan.index'))->assertStatus(200)->assertSee($pengaduan->nomor_tiket);
});

test('pengaduan tanpa foto ktp ditolak validasi', function () {
    Storage::fake('local');
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $response = $this->actingAs($cs)->from(route('cs.pengaduan.create'))
        ->post(route('cs.pengaduan.store'), dataFormPengaduan($transaksi, $cabang));

    $response->assertRedirect(route('cs.pengaduan.create'));
    $response->assertSessionHasErrors(['lampiran.foto_ktp']);
    expect(Pengaduan::count())->toBe(0);
});

test('berkas dengan format tidak diizinkan ditolak', function () {
    Storage::fake('local');
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $this->actingAs($cs)->from(route('cs.pengaduan.create'))
        ->post(route('cs.pengaduan.store'), dataFormPengaduan($transaksi, $cabang, [
            'lampiran' => ['foto_ktp' => [UploadedFile::fake()->create('ktp.docx', 50, 'application/msword')]],
        ]))
        ->assertSessionHasErrors(['lampiran.foto_ktp.0']);

    expect(Pengaduan::count())->toBe(0);
});

test('setiap pengaduan memperoleh nomor tiket unik berformat resmi', function () {
    $cabang = buatCabang('008', 'CABANG PALU BARAT');
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $p1 = buatPengaduan($cs, $transaksi);
    $p2 = buatPengaduan($cs, $transaksi, ['no_resi' => '654321']);

    $tanggal = now()->format('Ymd');
    expect($p1->nomor_tiket)->toStartWith("BS-{$tanggal}")
        ->and($p2->nomor_tiket)->toStartWith("BS-{$tanggal}")
        ->and($p1->nomor_tiket)->not->toBe($p2->nomor_tiket)
        ->and(Jurnal::formatTiketValid($p1->nomor_tiket))->toBeTrue()
        ->and(Jurnal::formatTiketValid($p2->nomor_tiket))->toBeTrue();
});

test('lingkup visibilitas cs: kiriman sendiri, cabang asal sama, atau rekan satu cabang', function () {
    $cabangA = buatCabang('001', 'CABANG UTAMA');
    $cabangB = buatCabang('003', 'CABANG POSO');
    $cabangC = buatCabang('004', 'CABANG LUWUK');
    $csA = buatCs($cabangA);
    $csA2 = buatCs($cabangA, ['username' => 'cs.utama.dua']);
    $csB = buatCs($cabangB);
    $csC = buatCs($cabangC);
    $transaksi = buatTransaksi();

    // CS A mengirim pengaduan dengan asal cabang B
    $pengaduan = buatPengaduan($csA, $transaksi, ['master_cabang_id' => $cabangB->id]);

    $this->actingAs($csA)->get(route('cs.pengaduan.show', $pengaduan))->assertStatus(200);   // pengirim
    $this->actingAs($csA2)->get(route('cs.pengaduan.show', $pengaduan))->assertStatus(200);  // rekan satu cabang pengirim
    $this->actingAs($csB)->get(route('cs.pengaduan.show', $pengaduan))->assertStatus(200);   // cabang asal
    $this->actingAs($csC)->get(route('cs.pengaduan.show', $pengaduan))->assertForbidden();   // tidak terkait

    $this->actingAs($csB)->get(route('cs.pengaduan.index'))->assertStatus(200)->assertSee($pengaduan->nomor_tiket);
    $this->actingAs($csC)->get(route('cs.pengaduan.index'))->assertStatus(200)->assertDontSee($pengaduan->nomor_tiket);
});

test('cs dapat mengedit pengaduan hanya saat status masih terkirim', function () {
    Storage::fake('local');
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $pengaduan = buatPengaduan($cs, $transaksi);

    // Tanpa lampiran KTP, update ditolak validasi (KTP wajib ada)
    $this->actingAs($cs)->from(route('cs.pengaduan.edit', $pengaduan))
        ->put(route('cs.pengaduan.update', $pengaduan), dataFormPengaduan($transaksi, $cabang))
        ->assertSessionHasErrors(['lampiran.foto_ktp']);

    $lampiran = buatLampiran($pengaduan);

    $this->actingAs($cs)->get(route('cs.pengaduan.edit', $pengaduan))
        ->assertStatus(200)
        ->assertSee($lampiran->nama_asli);

    $this->actingAs($cs)
        ->put(route('cs.pengaduan.update', $pengaduan), dataFormPengaduan($transaksi, $cabang, ['nama_nasabah' => 'Budi Baru']))
        ->assertRedirect(route('cs.pengaduan.show', $pengaduan));

    expect($pengaduan->fresh()->nama_nasabah)->toBe('BUDI BARU');

    // Setelah diterima pusat → terkunci
    $pengaduan->update(['status' => PengaduanStatus::Diterima, 'diterima_at' => now()]);

    $this->actingAs($cs)->get(route('cs.pengaduan.edit', $pengaduan))->assertForbidden();
    $this->actingAs($cs)->put(route('cs.pengaduan.update', $pengaduan), dataFormPengaduan($transaksi, $cabang))->assertForbidden();
    $this->actingAs($cs)->delete(route('cs.pengaduan.destroy', $pengaduan))->assertForbidden();

    $halaman = $this->actingAs($cs)->get(route('cs.pengaduan.show', $pengaduan));
    $halaman->assertStatus(200);
    $halaman->assertSee('Terkunci');
});

test('cs dapat menghapus satu lampiran tanpa menghapus pengaduannya', function () {
    Storage::fake('local');
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $pengaduan = buatPengaduan($cs, buatTransaksi());

    $lampiran = buatLampiran($pengaduan);

    $this->actingAs($cs)->delete(route('cs.pengaduan.lampiran.destroy', [$pengaduan, $lampiran]))
        ->assertRedirect();

    $this->assertDatabaseMissing('pengaduan_lampirans', ['id' => $lampiran->id]);
    $this->assertDatabaseHas('pengaduans', ['id' => $pengaduan->id]);
    Storage::disk('local')->assertMissing($lampiran->path);
});

test('cs dapat menghapus pengaduan yang masih terkirim beserta lampirannya', function () {
    Storage::fake('local');
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $pengaduan = buatPengaduan($cs, $transaksi);
    $lampiran = buatLampiran($pengaduan);

    $this->actingAs($cs)->delete(route('cs.pengaduan.destroy', $pengaduan))
        ->assertRedirect(route('cs.pengaduan.index'));

    $this->assertDatabaseMissing('pengaduans', ['id' => $pengaduan->id]);
    $this->assertDatabaseMissing('pengaduan_lampirans', ['pengaduan_id' => $pengaduan->id]);
    Storage::disk('local')->assertMissing($lampiran->path);
});

test('cs melihat status dan catatan dari pusat pada halaman detail', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();

    $pengaduan = buatPengaduan($cs, $transaksi, [
        'status' => PengaduanStatus::Ditolak,
        'ditolak_at' => now(),
        'catatan_pusat' => 'Foto KTP tidak terbaca, mohon unggah ulang.',
    ]);

    $response = $this->actingAs($cs)->get(route('cs.pengaduan.show', $pengaduan));

    $response->assertStatus(200);
    $response->assertSee('Ditolak');
    $response->assertSee('Foto KTP tidak terbaca, mohon unggah ulang.');
});
