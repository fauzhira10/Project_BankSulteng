<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pembatas peran: `role:admin`, `role:cs`, atau `role:admin,cs`.
 * Pengguna yang perannya tidak sesuai diarahkan kembali ke beranda perannya sendiri.
 * Pengguna nonaktif otomatis dikeluarkan dari sistem.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Akun yang dinonaktifkan admin tidak boleh melanjutkan sesi
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'Akun Anda telah dinonaktifkan. Silakan hubungi Admin Pusat.',
            ]);
        }

        $roleUser = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        $hasAccess = false;
        foreach ($roles as $r) {
            if ($r === 'superadmin') {
                if ($user->isSuperAdmin()) {
                    $hasAccess = true;
                    break;
                }
            } elseif ($roleUser === $r) {
                $hasAccess = true;
                break;
            }
        }

        if (! $hasAccess) {
            if ($request->expectsJson()) {
                abort(403, 'Anda tidak memiliki hak akses ke sumber daya ini.');
            }

            $pesan = in_array('superadmin', $roles, true)
                ? 'Menu Manajemen Pengguna hanya dapat diakses oleh Admin Utama.'
                : 'Anda tidak memiliki hak akses ke halaman tersebut.';

            return redirect()
                ->route($user->homeRoute())
                ->with('error', $pesan);
        }

        return $next($request);
    }
}
