<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Rules\KataSandi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Membuat akun Admin Pusat tanpa kata sandi bawaan.
 *
 * Seeder sengaja tidak lagi membuat akun admin produksi, sebab kata sandi yang
 * tertulis di dalam kode akan ikut tersebar bersama repositori dan dapat hidup
 * kembali setiap kali seeder dijalankan ulang. Kata sandi di sini diketik
 * petugas dan tidak pernah ditampilkan ke layar.
 */
class BuatAkunAdmin extends Command
{
    protected $signature = 'admin:buat
                            {--username= : Username untuk login (huruf, angka, titik, garis bawah, strip)}
                            {--name= : Nama lengkap petugas}
                            {--email= : Alamat email petugas}
                            {--superadmin : Jadikan Admin Utama yang berhak mengelola pengguna}';

    protected $description = 'Membuat akun Admin Pusat baru dengan kata sandi yang diketik petugas.';

    public function handle(): int
    {
        $this->info('Pembuatan akun Admin Pusat — Jurnal Keluhan Bank Sulteng');
        $this->newLine();

        $username = $this->option('username') ?: $this->ask('Username untuk login');
        $name = $this->option('name') ?: $this->ask('Nama lengkap petugas');
        $email = $this->option('email') ?: $this->ask('Alamat email petugas');

        $password = $this->secret('Kata sandi ('.lcfirst(KataSandi::keterangan()).', tidak ditampilkan)');
        $konfirmasi = $this->secret('Ulangi kata sandi');

        $validator = Validator::make([
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $konfirmasi,
        ], [
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/', Rule::unique('users', 'username')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            // Kebijakannya sama persis dengan form Manajemen Pengguna; lihat
            // App\Rules\KataSandi.
            'password' => KataSandi::aturan(),
        ], array_merge([
            'required' => ':attribute wajib diisi.',
            'username.unique' => 'Username tersebut sudah dipakai akun lain.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, garis bawah, atau strip.',
            'username.min' => 'Username minimal :min karakter.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email tersebut sudah dipakai akun lain.',
        ], KataSandi::pesan()), [
            'username' => 'Username',
            'name' => 'Nama lengkap',
            'email' => 'Alamat email',
            'password' => 'Kata sandi',
        ]);

        if ($validator->fails()) {
            $this->newLine();
            foreach ($validator->errors()->all() as $pesan) {
                $this->error('• '.$pesan);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::Admin,
            'is_superadmin' => (bool) $this->option('superadmin'),
            'master_cabang_id' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->newLine();
        $this->info("Akun {$user->labelRole()} untuk {$user->name} (@{$user->username}) berhasil dibuat.");

        if ($user->is_superadmin) {
            $this->line('Akun ini berstatus Admin Utama dan dapat mengelola pengguna lain.');
        }

        return self::SUCCESS;
    }
}
