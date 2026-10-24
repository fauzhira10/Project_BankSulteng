<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('halaman login membawa header keamanan dasar', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'none'")
        ->toContain("form-action 'self'")
        ->toContain("base-uri 'self'");
});

test('csp mengizinkan sumber daya luar yang memang dipakai tata letak', function () {
    $csp = $this->get('/login')->headers->get('Content-Security-Policy');

    // ApexCharts pada dashboard dan Google Fonts pada layout utama
    expect($csp)
        ->toContain('https://cdn.jsdelivr.net')
        ->toContain('https://fonts.googleapis.com')
        ->toContain('https://fonts.gstatic.com');
});

test('lampiran privat disajikan dengan nosniff', function () {
    Storage::fake('local');

    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();
    $pengaduan = buatPengaduan($cs, $transaksi);
    $lampiran = buatLampiran($pengaduan);

    $response = $this->actingAs($cs)->get(route('pengaduan.lampiran.show', [$pengaduan, $lampiran]));

    $response->assertStatus(200);
    // Berkas KTP disajikan inline; nosniff mencegah peramban menebak ulang
    // tipe berkas dan memperlakukannya sebagai HTML.
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('content-type', 'image/jpeg');
});

test('hsts hanya dipasang pada koneksi https', function () {
    $this->get('/login')->assertHeaderMissing('Strict-Transport-Security');

    $this->get('https://localhost/login')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});
