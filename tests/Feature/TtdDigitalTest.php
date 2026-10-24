<?php

use App\Models\Jurnal;
use App\Models\MasterCabang;
use App\Models\MasterPejabatTtd;
use App\Models\MasterTransaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('master pejabat ttd memiliki 4 pejabat default', function () {
    $this->seed(\Database\Seeders\MasterPejabatTtdSeeder::class);

    $defaults = MasterPejabatTtd::getAllDefault();

    expect($defaults)->toHaveCount(4)
        ->and($defaults[1]->nama)->toBe('MUJADID')
        ->and($defaults[2]->nama)->toBe('AYU FEBRIANTI')
        ->and($defaults[3]->nama)->toBe('WACHYUNI MADARAYU')
        ->and($defaults[4]->nama)->toBe('DIANA, ST');
});

test('admin dapat mengakses halaman kelola pejabat ttd', function () {
    $admin = buatAdmin();
    $this->seed(\Database\Seeders\MasterPejabatTtdSeeder::class);

    $response = $this->actingAs($admin)->get(route('admin.pejabat-ttd.index'));

    $response->assertStatus(200);
    $response->assertSee('Daftar Tanda Tangan');
    $response->assertSee('MUJADID');
    $response->assertSee('DIANA, ST');
});

test('admin dapat menambah pejabat ttd baru dan menjadikannya default', function () {
    $admin = buatAdmin();
    $this->seed(\Database\Seeders\MasterPejabatTtdSeeder::class);

    $response = $this->actingAs($admin)->post(route('admin.pejabat-ttd.store'), [
        'slot' => 4,
        'nama' => 'DR. IR. SULAIMAN, MT',
        'jabatan' => 'Pemimpin Divisi IT',
        'nip' => 'BS-123456',
        'is_default' => 1,
    ]);

    $response->assertRedirect(route('admin.pejabat-ttd.index'));

    $pejabatBaru = MasterPejabatTtd::where('nama', 'DR. IR. SULAIMAN, MT')->first();
    expect($pejabatBaru)->not->toBeNull()
        ->and($pejabatBaru->is_default)->toBeTrue();

    // Pejabat lama (DIANA) harus tidak lagi default
    $diana = MasterPejabatTtd::where('nama', 'DIANA, ST')->first();
    expect($diana->is_default)->toBeFalse();
});

test('dokumen cetak menampilkan konfigurasi ttd dan switcher pejabat', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();
    $this->seed(\Database\Seeders\MasterPejabatTtdSeeder::class);

    $jurnal = Jurnal::create(dataJurnalDb($cabang, $transaksi, [
        'status' => 'Done',
        'keterangan_log' => 'Selesai diproses',
    ]));

    $response = $this->actingAs($admin)->get(route('jurnal.download', $jurnal->id));

    $response->assertStatus(200);
    $response->assertSee('MUJADID');
    $response->assertSee('AYU FEBRIANTI');
    $response->assertSee('WACHYUNI MADARAYU');
    $response->assertSee('DIANA, ST');
    $response->assertSee('Kosongkan Semua (TTD Basah)');
    $response->assertSee('Pasang TTD (Staf & Unit)', false);
    $response->assertSee('Pasang Semua TTD');
    $response->assertSee('chipSlot_1');
    $response->assertSee('chipSlot_2');
    $response->assertSee('chipSlot_3');
    $response->assertSee('chipSlot_4');
    $response->assertSee('ttd-placeholder-kosong');
    $response->assertDontSee('Ruang TTD Basah');

    // Pastikan konfigurasi default jurnal adalah kosong (mode manual/basah)
    $config = $jurnal->getTtdConfig();
    expect($config['mode'])->toBe('manual')
        ->and($config['slots'][1]['is_kosong'])->toBeTrue()
        ->and($config['slots'][2]['is_kosong'])->toBeTrue()
        ->and($config['slots'][3]['is_kosong'])->toBeTrue()
        ->and($config['slots'][4]['is_kosong'])->toBeTrue();
});

test('admin dapat menyimpan snapshot pilihan ttd ke jurnal', function () {
    $admin = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();
    $this->seed(\Database\Seeders\MasterPejabatTtdSeeder::class);

    $jurnal = Jurnal::create(dataJurnalDb($cabang, $transaksi, [
        'status' => 'Done',
        'keterangan_log' => 'Selesai diproses',
    ]));

    $payload = [
        'mode' => 'digital',
        'slots' => [
            1 => ['nama' => 'MUJADID', 'jabatan' => 'Staf Layanan', 'is_kosong' => false, 'ttd_image' => 'data:image/png;base64,abc'],
            2 => ['nama' => 'BUDI SANTOSO, S.Kom', 'jabatan' => 'Plt. Pemimpin Unit', 'is_kosong' => false, 'ttd_image' => 'data:image/png;base64,def'],
            3 => ['nama' => 'WACHYUNI MADARAYU', 'jabatan' => 'PINBAG E- CHANNEL', 'is_kosong' => true, 'ttd_image' => ''],
            4 => ['nama' => 'DIANA, ST', 'jabatan' => 'Pemimpin Divisi IT', 'is_kosong' => true, 'ttd_image' => ''],
        ],
    ];

    $response = $this->actingAs($admin)->postJson(route('api.jurnal.simpan_ttd', $jurnal->id), $payload);

    $response->assertStatus(200)
        ->assertJson(['status' => 'success']);

    $jurnalFresh = $jurnal->fresh();
    expect($jurnalFresh->ttd_data)->not->toBeNull()
        ->and($jurnalFresh->ttd_data['slots']['2']['nama'])->toBe('BUDI SANTOSO, S.Kom')
        ->and($jurnalFresh->ttd_data['slots']['3']['is_kosong'])->toBeTrue();
});

test('4 pejabat default tidak dapat dihapus', function () {
    $admin = buatAdmin();
    $this->seed(\Database\Seeders\MasterPejabatTtdSeeder::class);

    $mujadid = MasterPejabatTtd::where('nama', 'MUJADID')->first();
    expect($mujadid->isDeletable())->toBeFalse();

    $response = $this->actingAs($admin)->delete(route('admin.pejabat-ttd.destroy', $mujadid->id));
    $response->assertRedirect(route('admin.pejabat-ttd.index'))
        ->assertSessionHas('error');

    expect(MasterPejabatTtd::find($mujadid->id))->not->toBeNull();
});

test('pejabat tambahan selain 4 default dapat dihapus', function () {
    $admin = buatAdmin();
    $this->seed(\Database\Seeders\MasterPejabatTtdSeeder::class);

    $pejabatBaru = MasterPejabatTtd::create([
        'slot' => 1,
        'nama' => 'AHMAD FAUZI',
        'jabatan' => 'Plt. Staf',
        'is_aktif' => true,
        'is_default' => false,
    ]);

    expect($pejabatBaru->isDeletable())->toBeTrue();

    $response = $this->actingAs($admin)->delete(route('admin.pejabat-ttd.destroy', $pejabatBaru->id));
    $response->assertRedirect(route('admin.pejabat-ttd.index'))
        ->assertSessionHas('success');

    expect(MasterPejabatTtd::find($pejabatBaru->id))->toBeNull();
});

test('nama dan jabatan 4 pejabat default terkunci dan tidak dapat diubah saat update', function () {
    $admin = buatAdmin();
    $this->seed(\Database\Seeders\MasterPejabatTtdSeeder::class);

    $defaultPejabats = [
        ['nama' => 'MUJADID', 'slot' => 1],
        ['nama' => 'AYU FEBRIANTI', 'slot' => 2],
        ['nama' => 'WACHYUNI MADARAYU', 'slot' => 3],
        ['nama' => 'DIANA, ST', 'slot' => 4],
    ];

    foreach ($defaultPejabats as $item) {
        $pejabat = MasterPejabatTtd::where('slot', $item['slot'])->where('nama', $item['nama'])->first();
        expect($pejabat)->not->toBeNull()
            ->and($pejabat->isDefaultMaster())->toBeTrue()
            ->and($pejabat->is_locked)->toBeTrue();

        $originalNama = $pejabat->nama;
        $originalJabatan = $pejabat->jabatan;

        // Coba kirim perubahan nama dan jabatan lewat PUT request
        $response = $this->actingAs($admin)->put(route('admin.pejabat-ttd.update', $pejabat->id), [
            'nama' => 'NAMA DIUBAH ILEGAL',
            'jabatan' => 'JABATAN DIUBAH ILEGAL',
            'is_aktif' => 1,
            'ttd_canvas' => 'data:image/svg+xml;base64,samplebaru',
        ]);

        $response->assertRedirect(route('admin.pejabat-ttd.index'))
            ->assertSessionHas('success');

        $pejabatFresh = $pejabat->fresh();
        // Nama dan jabatan HARUS tetap sama (terkunci), hanya TTD yang berubah
        expect($pejabatFresh->nama)->toBe($originalNama)
            ->and($pejabatFresh->jabatan)->toBe($originalJabatan)
            ->and($pejabatFresh->ttd_image)->toBe('data:image/svg+xml;base64,samplebaru');
    }
});

test('pejabat non-default dapat mengubah nama dan jabatannya', function () {
    $admin = buatAdmin();
    $this->seed(\Database\Seeders\MasterPejabatTtdSeeder::class);

    $budi = MasterPejabatTtd::where('nama', 'BUDI SANTOSO, S.Kom')->first();
    expect($budi->isDefaultMaster())->toBeFalse()
        ->and($budi->is_locked)->toBeFalse();

    $response = $this->actingAs($admin)->put(route('admin.pejabat-ttd.update', $budi->id), [
        'nama' => 'BUDI SANTOSO, M.Kom',
        'jabatan' => 'Pemimpin Unit Senior',
        'is_aktif' => 1,
    ]);

    $response->assertRedirect(route('admin.pejabat-ttd.index'))
        ->assertSessionHas('success');

    $budiFresh = $budi->fresh();
    expect($budiFresh->nama)->toBe('BUDI SANTOSO, M.KOM')
        ->and($budiFresh->jabatan)->toBe('Pemimpin Unit Senior');
});


