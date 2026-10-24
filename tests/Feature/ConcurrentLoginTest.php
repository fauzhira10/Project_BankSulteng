<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('pengguna dapat login secara normal jika belum ada sesi aktif', function () {
    $cabang = buatCabang();
    buatCs($cabang, ['username' => 'cs.palu']);

    $response = $this->post('/login', [
        'username' => 'cs.palu',
        'password' => 'cs12345',
    ]);

    $response->assertRedirect(route('cs.dashboard'));
    $this->assertAuthenticated();
});

test('login kedua ditolak jika akun yang sama sedang aktif di komputer atau sesi lain', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang, ['username' => 'cs.palu']);

    // Simulasikan sesi aktif di komputer/perangkat lain yang masih aktif (5 menit yang lalu)
    DB::table('sessions')->insert([
        'id' => 'sesi-komputer-pertama',
        'user_id' => $cs->id,
        'ip_address' => '192.168.1.50',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        'payload' => base64_encode(serialize([])),
        'last_activity' => now()->subMinutes(5)->timestamp,
    ]);

    // Percobaan login dari komputer/sesi kedua
    $response = $this->from('/login')->post('/login', [
        'username' => 'cs.palu',
        'password' => 'cs12345',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('username');
    $this->assertGuest();

    // Pastikan sesi komputer pertama tetap ada dan tidak terganggu
    $this->assertDatabaseHas('sessions', [
        'id' => 'sesi-komputer-pertama',
        'user_id' => $cs->id,
    ]);
});

test('login kedua diizinkan jika sesi pertama sudah logout', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang, ['username' => 'cs.palu']);

    // Login komputer pertama
    $this->post('/login', [
        'username' => 'cs.palu',
        'password' => 'cs12345',
    ]);
    $this->assertAuthenticated();

    // Komputer pertama menekan logout
    $this->post('/logout');
    $this->assertGuest();

    // Komputer kedua sekarang mencoba login
    $response = $this->post('/login', [
        'username' => 'cs.palu',
        'password' => 'cs12345',
    ]);

    $response->assertRedirect(route('cs.dashboard'));
    $this->assertAuthenticated();
});

test('login kedua diizinkan jika sesi pertama sudah kedaluwarsa lebih dari 30 menit', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang, ['username' => 'cs.palu']);

    // Simulasikan sesi komputer pertama yang ditinggal tanpa logout selama 35 menit
    DB::table('sessions')->insert([
        'id' => 'sesi-komputer-lama-hangus',
        'user_id' => $cs->id,
        'ip_address' => '192.168.1.50',
        'user_agent' => 'Mozilla/5.0',
        'payload' => base64_encode(serialize([])),
        'last_activity' => now()->subMinutes(35)->timestamp,
    ]);

    // Percobaan login dari komputer kedua
    $response = $this->post('/login', [
        'username' => 'cs.palu',
        'password' => 'cs12345',
    ]);

    $response->assertRedirect(route('cs.dashboard'));
    $this->assertAuthenticated();

    // Sesi lama yang sudah lewat batas inaktivitas harus dibersihkan
    $this->assertDatabaseMissing('sessions', [
        'id' => 'sesi-komputer-lama-hangus',
    ]);
});

test('admin utama dapat mereset sesi aktif pengguna lain', function () {
    $admin = buatSuperAdmin();
    $cabang = buatCabang();
    $cs = buatCs($cabang, ['username' => 'cs.palu']);

    // Simulasikan sesi aktif cs.palu
    DB::table('sessions')->insert([
        'id' => 'sesi-cs-terkunci',
        'user_id' => $cs->id,
        'ip_address' => '192.168.1.50',
        'user_agent' => 'Mozilla/5.0',
        'payload' => base64_encode(serialize([])),
        'last_activity' => now()->subMinutes(2)->timestamp,
    ]);

    // Admin mereset sesi aktif cs.palu
    $response = $this->actingAs($admin)->post(route('admin.pengguna.reset_sesi', $cs));

    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('sessions', [
        'user_id' => $cs->id,
    ]);
});

test('admin pusat biasa (non-superadmin) tidak dapat mereset sesi pengguna', function () {
    $adminBiasa = buatAdmin();
    $cabang = buatCabang();
    $cs = buatCs($cabang, ['username' => 'cs.palu']);

    $response = $this->actingAs($adminBiasa)->post(route('admin.pengguna.reset_sesi', $cs));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});

test('cs tidak dapat mereset sesi pengguna', function () {
    $cabang = buatCabang();
    $cs1 = buatCs($cabang, ['username' => 'cs.satu']);
    $cs2 = buatCs($cabang, ['username' => 'cs.dua']);

    $response = $this->actingAs($cs1)->post(route('admin.pengguna.reset_sesi', $cs2));

    // Dialihkan karena route dibatasi role:admin & role:superadmin
    $response->assertRedirect(route('cs.dashboard'));
});
