<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\MasterCabang;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder akun pengguna.
 *
 * Aturan penting: seeder ini **tidak pernah menimpa akun yang sudah ada** dan
 * **tidak pernah membuat akun berkata-sandi bawaan di luar lingkungan
 * pengembangan**. Sebelumnya seeder memakai updateOrCreate, sehingga satu kali
 * `db:seed` di server produksi mengembalikan kata sandi admin ke nilai yang
 * tertulis di repositori ini. Akun produksi dibuat lewat `php artisan admin:buat`.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->akunAdminUtama();

        // Akun contoh CS hanya untuk pengembangan & pengujian.
        if ($this->lingkunganPengembangan()) {
            $this->akunDemoCs();
        }
    }

    protected function lingkunganPengembangan(): bool
    {
        return app()->environment('local', 'testing');
    }

    /**
     * Akun Admin Pusat. Tidak dibuat ulang bila sudah ada, dan di produksi
     * tidak dibuat sama sekali — petugas menjalankan `admin:buat` sendiri.
     */
    protected function akunAdminUtama(): void
    {
        if (User::where('role', UserRole::Admin->value)->exists()) {
            $this->command?->info('Akun Admin Pusat sudah ada — seeder tidak mengubah kata sandinya.');

            return;
        }

        if (! $this->lingkunganPengembangan()) {
            $this->command?->warn('Akun Admin Pusat belum ada dan tidak dibuat otomatis di lingkungan ini.');
            $this->command?->warn('Jalankan: php artisan admin:buat --superadmin');

            return;
        }

        User::create([
            'username' => 'admin',
            'name' => 'Administrator Bank Sulteng',
            'email' => 'admin@banksulteng.co.id',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
            'role' => UserRole::Admin,
            'is_superadmin' => true,
            'master_cabang_id' => null,
            'is_active' => true,
        ]);

        $this->command?->warn('Akun admin pengembangan dibuat (admin / admin123) — jangan dipakai di server produksi.');
    }

    /**
     * Akun CS contoh untuk Cabang Utama, hanya di lingkungan pengembangan.
     */
    protected function akunDemoCs(): void
    {
        $cabangUtama = MasterCabang::where('kode_cabang', '001')->first();

        if (! $cabangUtama || User::where('username', 'cs.palu')->exists()) {
            return;
        }

        User::create([
            'username' => 'cs.palu',
            'name' => 'CS Cabang Utama Palu',
            'email' => 'cs.palu@banksulteng.co.id',
            'password' => Hash::make('cs12345'),
            'email_verified_at' => now(),
            'role' => UserRole::Cs,
            'master_cabang_id' => $cabangUtama->id,
            'is_active' => true,
        ]);
    }
}
