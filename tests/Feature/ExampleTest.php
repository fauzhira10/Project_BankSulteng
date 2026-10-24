<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('halaman utama mengalihkan tamu ke login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

// Catatan: Dashboard admin (/) memakai fungsi MySQL YEAR() yang tidak tersedia di SQLite (DB pengujian),
// sehingga halaman admin yang diuji di sini adalah Data Keluhan.
test('halaman admin dapat diakses oleh admin yang sudah login', function () {
    $response = $this->actingAs(buatAdmin())->get('/jurnal/data');

    $response->assertStatus(200);
});
