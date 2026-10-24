<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Pesan galat pertama pada kolom username.
 *
 * Bentuk yang tersimpan di sesi berbeda antar jalur: penguncian dilempar sebagai
 * ValidationException (ViewErrorBag), sedangkan kegagalan kata sandi memakai
 * back()->withErrors() yang pada driver sesi array tersimpan sebagai array biasa.
 */
function pesanGalatLogin(): string
{
    $errors = session('errors');

    if ($errors === null) {
        return '';
    }

    if (is_object($errors)) {
        return (string) $errors->first('username');
    }

    $bag = $errors['default'] ?? [];
    $pesan = is_array($bag) ? ($bag['username'] ?? []) : [];

    return (string) (is_array($pesan) ? ($pesan[0] ?? '') : $pesan);
}

/**
 * Lakukan satu percobaan login dengan kata sandi yang salah.
 */
function loginGagal(string $username = 'cs.palu'): void
{
    test()->from('/login')->post('/login', [
        'username' => $username,
        'password' => 'kata-sandi-salah',
    ]);
}

test('login dikunci sementara setelah lima percobaan gagal berturut-turut', function () {
    $cabang = buatCabang();
    buatCs($cabang, ['username' => 'cs.palu']);

    for ($i = 0; $i < 5; $i++) {
        loginGagal();
    }

    // Percobaan berikutnya ditolak pembatas — kata sandi yang benar pun
    // tidak lagi diperiksa selama masa penguncian.
    $this->from('/login')->post('/login', [
        'username' => 'cs.palu',
        'password' => 'cs12345',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
    expect(pesanGalatLogin())->toContain('Terlalu banyak percobaan masuk');
});

test('percobaan gagal pada satu akun tidak mengunci akun lain', function () {
    $cabang = buatCabang();
    buatCs($cabang, ['username' => 'cs.satu']);
    buatCs($cabang, ['username' => 'cs.dua']);

    for ($i = 0; $i < 5; $i++) {
        loginGagal('cs.satu');
    }

    // Akun kedua tetap dapat masuk seperti biasa
    $this->post('/login', [
        'username' => 'cs.dua',
        'password' => 'cs12345',
    ])->assertRedirect(route('cs.dashboard'));

    $this->assertAuthenticated();
});

test('hitungan percobaan gagal dinolkan setelah login berhasil', function () {
    $cabang = buatCabang();
    buatCs($cabang, ['username' => 'cs.palu']);

    for ($i = 0; $i < 4; $i++) {
        loginGagal();
    }

    $this->post('/login', [
        'username' => 'cs.palu',
        'password' => 'cs12345',
    ])->assertRedirect(route('cs.dashboard'));

    $this->post('/logout');

    // Hitungan sudah dinolkan, jadi empat kegagalan berikutnya belum mengunci:
    // login dengan kata sandi benar tetap diterima.
    for ($i = 0; $i < 4; $i++) {
        loginGagal();
    }

    $this->post('/login', [
        'username' => 'cs.palu',
        'password' => 'cs12345',
    ])->assertRedirect(route('cs.dashboard'));

    $this->assertAuthenticated();
});

test('penolakan karena sesi ganda tidak dihitung sebagai percobaan gagal', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang, ['username' => 'cs.palu']);

    DB::table('sessions')->insert([
        'id' => 'sesi-komputer-pertama',
        'user_id' => $cs->id,
        'ip_address' => '192.168.1.50',
        'user_agent' => 'Mozilla/5.0',
        'payload' => base64_encode(serialize([])),
        'last_activity' => now()->subMinutes(5)->timestamp,
    ]);

    // Lima kali memakai kata sandi yang BENAR namun ditolak karena sesi ganda.
    // Ini bukan indikasi serangan, jadi tidak boleh menyebabkan penguncian.
    for ($i = 0; $i < 5; $i++) {
        $response = $this->from('/login')->post('/login', [
            'username' => 'cs.palu',
            'password' => 'cs12345',
        ]);
    }

    $response->assertSessionHasErrors('username');
    $this->assertGuest();

    // Setelah sesi lama dibersihkan, akun harus langsung bisa masuk —
    // bukti hitungan percobaan gagal tidak pernah bertambah.
    DB::table('sessions')->where('id', 'sesi-komputer-pertama')->delete();

    $this->post('/login', [
        'username' => 'cs.palu',
        'password' => 'cs12345',
    ])->assertRedirect(route('cs.dashboard'));

    $this->assertAuthenticated();
});
