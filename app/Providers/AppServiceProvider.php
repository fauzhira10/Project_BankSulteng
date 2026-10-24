<?php

namespace App\Providers;

use App\Listeners\CatatKejadianLogin;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Gate umum untuk fitur yang hanya boleh diakses Admin Pusat
        Gate::define('admin-only', fn (User $user) => $user->isAdmin());

        // Gate khusus untuk fitur yang hanya boleh diakses Admin Utama (Super Admin)
        Gate::define('superadmin-only', fn (User $user) => $user->isSuperAdmin());

        // Jejak audit kejadian autentikasi. Didaftarkan eksplisit (bukan lewat
        // penemuan otomatis) supaya jelas terbaca apa yang terpasang.
        Event::listen(Failed::class, [CatatKejadianLogin::class, 'gagal']);
        Event::listen(Lockout::class, [CatatKejadianLogin::class, 'terkunci']);
    }
}
