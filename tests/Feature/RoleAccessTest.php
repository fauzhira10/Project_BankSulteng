<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tamu yang belum login dialihkan ke halaman login', function () {
    $this->get('/cs')->assertRedirect('/login');
    $this->get('/jurnal/data')->assertRedirect('/login');
});

test('cs tidak dapat membuka halaman admin dan dialihkan ke dashboard cs', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);

    $this->actingAs($cs)->get('/jurnal/data')->assertRedirect(route('cs.dashboard'));
    $this->actingAs($cs)->get('/pengaduan-masuk')->assertRedirect(route('cs.dashboard'));
    $this->actingAs($cs)->get('/pengguna')->assertRedirect(route('cs.dashboard'));
});

test('admin tidak dapat membuka halaman cs dan dialihkan ke dashboard admin', function () {
    $admin = buatAdmin();

    $this->actingAs($admin)->get('/cs/pengaduan')->assertRedirect(route('dashboard'));
    $this->actingAs($admin)->get('/cs/pengaduan/input')->assertRedirect(route('dashboard'));
});

test('login mengarahkan pengguna sesuai perannya', function () {
    $cabang = buatCabang();
    buatCs($cabang, ['username' => 'cs.palu']);
    buatAdmin(['username' => 'admin']);

    $this->post('/login', ['username' => 'cs.palu', 'password' => 'cs12345'])
        ->assertRedirect(route('cs.dashboard'));
    $this->post('/logout');

    $this->post('/login', ['username' => 'admin', 'password' => 'admin123'])
        ->assertRedirect(route('dashboard'));
});

test('akun nonaktif tidak dapat login', function () {
    $cabang = buatCabang();
    buatCs($cabang, ['username' => 'cs.nonaktif', 'is_active' => false]);

    $response = $this->from('/login')->post('/login', ['username' => 'cs.nonaktif', 'password' => 'cs12345']);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('username');
    $this->assertGuest();
});

test('dashboard cs dan navigasi cs dapat diakses oleh cs', function () {
    $cabang = buatCabang('003', 'CABANG POSO');
    $cs = buatCs($cabang);

    $response = $this->actingAs($cs)->get('/cs');

    $response->assertStatus(200);
    $response->assertSee('Input Pengaduan');
    $response->assertSee('Data Pengaduan');
    $response->assertSee('CABANG POSO');
    $response->assertDontSee('Rekap Laporan Keluhan');
});

test('sidebar admin utama menampilkan menu manajemen pengguna', function () {
    $superadmin = buatSuperAdmin();

    $response = $this->actingAs($superadmin)->get('/jurnal/data');

    $response->assertStatus(200);
    $response->assertSee('Pengaduan Masuk');
    $response->assertSee('Manajemen Pengguna');
});

test('sidebar admin biasa tidak menampilkan menu manajemen pengguna', function () {
    $adminBiasa = buatAdmin();

    $response = $this->actingAs($adminBiasa)->get('/jurnal/data');

    $response->assertStatus(200);
    $response->assertSee('Pengaduan Masuk');
    $response->assertDontSee('Manajemen Pengguna');
});

test('admin utama dapat mengakses halaman manajemen pengguna', function () {
    $superadmin = buatSuperAdmin();

    $response = $this->actingAs($superadmin)->get('/pengguna');

    $response->assertStatus(200);
    $response->assertSee('Daftar Akun Pengguna');
});

test('admin biasa tidak dapat membuka halaman manajemen pengguna dan dialihkan ke dashboard', function () {
    $adminBiasa = buatAdmin();

    $response = $this->actingAs($adminBiasa)->get('/pengguna');

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});
