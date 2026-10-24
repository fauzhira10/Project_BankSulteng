<?php

use App\Models\AuditTrail;
use App\Models\Jurnal;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Bersihkan berkas cadangan yang ditulis ke disk selama pengujian.
 */
afterEach(function () {
    $folder = storage_path('app/private/cadangan');

    if (is_dir($folder)) {
        foreach (glob($folder.DIRECTORY_SEPARATOR.'cadangan-jurnal-*.xlsx') as $berkas) {
            @unlink($berkas);
        }
    }
});

test('admin pusat biasa tidak dapat menghapus seluruh data jurnal', function () {
    $adminBiasa = buatAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();
    Jurnal::create(dataJurnalDb($cabang, $transaksi));

    $this->actingAs($adminBiasa)
        ->delete(route('jurnal.reset_all'), ['password' => 'admin123'])
        ->assertRedirect(route('dashboard'));

    expect(Jurnal::count())->toBe(1);
});

test('reset ditolak bila kata sandi tidak disertakan', function () {
    $admin = buatSuperAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();
    Jurnal::create(dataJurnalDb($cabang, $transaksi));

    $this->actingAs($admin)
        ->delete(route('jurnal.reset_all'))
        ->assertSessionHasErrors('password');

    expect(Jurnal::count())->toBe(1);
});

test('reset ditolak bila kata sandi salah', function () {
    $admin = buatSuperAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();
    Jurnal::create(dataJurnalDb($cabang, $transaksi));

    $this->actingAs($admin)
        ->delete(route('jurnal.reset_all'), ['password' => 'bukan-kata-sandinya'])
        ->assertSessionHasErrors('password');

    expect(Jurnal::count())->toBe(1);
});

test('reset berhasil membuat cadangan dan mencatat jejak audit', function () {
    $admin = buatSuperAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();
    Jurnal::create(dataJurnalDb($cabang, $transaksi));
    Jurnal::create(dataJurnalDb($cabang, $transaksi, [
        'nama_nasabah' => 'SITI AMINAH',
        'no_resi' => '654321',
    ]));

    $this->actingAs($admin)
        ->delete(route('jurnal.reset_all'), ['password' => 'admin123'])
        ->assertRedirect(route('jurnal.index'));

    expect(Jurnal::count())->toBe(0);

    // Cadangan Excel tertulis sebelum baris pertama dihapus
    $cadangan = glob(storage_path('app/private/cadangan').DIRECTORY_SEPARATOR.'cadangan-jurnal-*.xlsx');
    expect($cadangan)->not->toBeEmpty();

    $jejak = AuditTrail::where('aksi', 'jurnal.reset_massal')->first();

    expect($jejak)->not->toBeNull()
        ->and($jejak->user_id)->toBe($admin->id)
        ->and($jejak->nilai_lama['jumlah_baris'])->toBe(2)
        ->and($jejak->keterangan)->toContain('cadangan-jurnal-');
});

test('jejak audit penghapusan massal tidak ikut terhapus oleh reset', function () {
    $admin = buatSuperAdmin();
    $cabang = buatCabang();
    $transaksi = buatTransaksi();
    Jurnal::create(dataJurnalDb($cabang, $transaksi));

    $sebelum = AuditTrail::count();
    expect($sebelum)->toBeGreaterThan(0);

    $this->actingAs($admin)->delete(route('jurnal.reset_all'), ['password' => 'admin123']);

    // Mass delete melewati observer, tetapi jejak yang sudah ada harus tetap utuh
    expect(AuditTrail::where('aksi', 'jurnal.dibuat')->count())->toBe(1);
});

test('tombol dan modal hapus semua data hanya tampil bagi admin utama', function () {
    $cabang = buatCabang();
    $transaksi = buatTransaksi();
    Jurnal::create(dataJurnalDb($cabang, $transaksi));

    // Admin Utama melihat tombolnya beserta kolom konfirmasi kata sandi
    $this->actingAs(buatSuperAdmin())
        ->get(route('jurnal.index'))
        ->assertStatus(200)
        ->assertSee('Hapus Semua Data')
        ->assertSee('resetAllPassword');

    // Admin pusat biasa tidak melihatnya sama sekali
    $this->actingAs(buatAdmin())
        ->get(route('jurnal.index'))
        ->assertStatus(200)
        ->assertDontSee('Hapus Semua Data')
        ->assertDontSee('resetAllPassword');
});
