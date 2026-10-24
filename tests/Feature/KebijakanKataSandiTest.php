<?php

use App\Models\User;
use App\Rules\KataSandi;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * Kebijakan kata sandi berlaku sama di kedua pintu pembuatan akun: form
 * Manajemen Pengguna dan perintah admin:buat. Sebelumnya form web hanya
 * menuntut 6 karakter, sehingga akun Admin Pusat yang dibuat lewat web bisa
 * jauh lebih lemah daripada yang dibuat di server.
 */
function isianAkunBaru(int $cabangId, string $sandi): array
{
    return [
        'name' => 'CS Poso',
        'username' => 'cs.poso',
        'email' => 'cs.poso@banksulteng.co.id',
        'role' => 'cs',
        'master_cabang_id' => $cabangId,
        'password' => $sandi,
        'password_confirmation' => $sandi,
    ];
}

test('kata sandi yang lebih pendek dari kebijakan ditolak form pengguna', function () {
    $cabang = buatCabang('003', 'CABANG POSO');
    $admin = buatSuperAdmin();

    $this->actingAs($admin)
        ->from(route('admin.pengguna.create'))
        ->post(route('admin.pengguna.store'), isianAkunBaru($cabang->id, 'rahasia123'))
        ->assertSessionHasErrors('password');

    $this->assertDatabaseMissing('users', ['username' => 'cs.poso']);
});

test('kata sandi tanpa angka ditolak walau panjangnya cukup', function () {
    $cabang = buatCabang('003', 'CABANG POSO');
    $admin = buatSuperAdmin();

    $this->actingAs($admin)
        ->from(route('admin.pengguna.create'))
        ->post(route('admin.pengguna.store'), isianAkunBaru($cabang->id, 'katasandipanjangsekali'))
        ->assertSessionHasErrors('password');

    $this->assertDatabaseMissing('users', ['username' => 'cs.poso']);
});

test('kata sandi yang memenuhi kebijakan diterima', function () {
    $cabang = buatCabang('003', 'CABANG POSO');
    $admin = buatSuperAdmin();

    $this->actingAs($admin)
        ->post(route('admin.pengguna.store'), isianAkunBaru($cabang->id, 'kunciAtmPoso2026'))
        ->assertRedirect(route('admin.pengguna.index'));

    $baru = User::where('username', 'cs.poso')->first();

    expect($baru)->not->toBeNull()
        ->and(Hash::check('kunciAtmPoso2026', $baru->password))->toBeTrue();
});

test('mengubah akun tanpa mengisi kata sandi tidak menyentuh kata sandi lama', function () {
    $cabang = buatCabang('003', 'CABANG POSO');
    $admin = buatSuperAdmin();
    $cs = buatCs($cabang);

    $this->actingAs($admin)->put(route('admin.pengguna.update', $cs), [
        'name' => 'Nama Baru',
        'username' => $cs->username,
        'email' => $cs->email,
        'role' => 'cs',
        'master_cabang_id' => $cabang->id,
        'password' => '',
        'password_confirmation' => '',
    ])->assertRedirect(route('admin.pengguna.index'));

    $cs->refresh();

    expect($cs->name)->toBe('Nama Baru')
        ->and(Hash::check('cs12345', $cs->password))->toBeTrue();
});

test('mengganti kata sandi lewat form edit tetap tunduk pada kebijakan', function () {
    $cabang = buatCabang('003', 'CABANG POSO');
    $admin = buatSuperAdmin();
    $cs = buatCs($cabang);

    $this->actingAs($admin)->from(route('admin.pengguna.edit', $cs))
        ->put(route('admin.pengguna.update', $cs), [
            'name' => $cs->name,
            'username' => $cs->username,
            'email' => $cs->email,
            'role' => 'cs',
            'master_cabang_id' => $cabang->id,
            'password' => 'pendek1',
            'password_confirmation' => 'pendek1',
        ])->assertSessionHasErrors('password');

    expect(Hash::check('cs12345', $cs->fresh()->password))->toBeTrue();
});

test('perintah admin buat memakai kebijakan yang sama', function () {
    $tanya = 'Kata sandi ('.lcfirst(KataSandi::keterangan()).', tidak ditampilkan)';

    $this->artisan('admin:buat', [
        '--username' => 'admin.pusat',
        '--name' => 'Admin Pusat',
        '--email' => 'admin.pusat@banksulteng.co.id',
    ])
        ->expectsQuestion($tanya, 'rahasia123')
        ->expectsQuestion('Ulangi kata sandi', 'rahasia123')
        ->assertExitCode(Command::FAILURE);

    $this->assertDatabaseMissing('users', ['username' => 'admin.pusat']);

    $this->artisan('admin:buat', [
        '--username' => 'admin.pusat',
        '--name' => 'Admin Pusat',
        '--email' => 'admin.pusat@banksulteng.co.id',
        '--superadmin' => true,
    ])
        ->expectsQuestion($tanya, 'kunciPusat2026aman')
        ->expectsQuestion('Ulangi kata sandi', 'kunciPusat2026aman')
        ->assertExitCode(Command::SUCCESS);

    $this->assertDatabaseHas('users', ['username' => 'admin.pusat', 'role' => 'admin', 'is_superadmin' => true]);
});
