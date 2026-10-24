<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\MasterCabang;
use App\Models\User;
use App\Rules\KataSandi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/**
 * Manajemen akun pengguna (Admin Pusat & CS Cabang) oleh Admin Pusat.
 * Akun tidak dihapus, hanya dinonaktifkan agar riwayat pengaduan tetap utuh.
 */
class PenggunaController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('cabang')->withCount('pengaduans');

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('name', 'LIKE', "%{$q}%")
                    ->orWhere('username', 'LIKE', "%{$q}%")
                    ->orWhere('email', 'LIKE', "%{$q}%")
                    ->orWhereHas('cabang', fn ($c) => $c->where('nama_cabang', 'LIKE', "%{$q}%")->orWhere('kode_cabang', 'LIKE', "%{$q}%"));
            });
        }

        if ($request->filled('role') && in_array($request->role, UserRole::values(), true)) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('role')->orderBy('name')->paginate(20)->withQueryString();

        $ringkasan = [
            'admin' => User::where('role', UserRole::Admin->value)->count(),
            'cs' => User::where('role', UserRole::Cs->value)->count(),
            'nonaktif' => User::where('is_active', false)->count(),
        ];

        $timeoutMenit = config('session.concurrent_timeout', 30);
        $batasWaktu = now()->subMinutes($timeoutMenit)->timestamp;
        $sesiAktifUserIds = [];

        if (Schema::hasTable('sessions')) {
            $sesiAktifUserIds = DB::table('sessions')
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', $batasWaktu)
                ->pluck('user_id')
                ->unique()
                ->toArray();
        }

        return view('admin.pengguna_index', compact('users', 'ringkasan', 'sesiAktifUserIds'));
    }

    public function create()
    {
        return view('admin.pengguna_form', [
            'user' => null,
            'cabangs' => MasterCabang::orderBy('kode_cabang')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request, null);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'master_cabang_id' => $data['role'] === UserRole::Cs->value ? $data['master_cabang_id'] : null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('admin.pengguna.index')
            ->with('success', "Akun {$user->labelRole()} untuk {$user->name} (@{$user->username}) berhasil dibuat.");
    }

    public function edit(User $user)
    {
        return view('admin.pengguna_form', [
            'user' => $user,
            'cabangs' => MasterCabang::orderBy('kode_cabang')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validasi($request, $user);

        // Admin tidak boleh menurunkan perannya sendiri
        if ($user->is($request->user()) && $data['role'] !== UserRole::Admin->value) {
            return back()->withInput()->withErrors(['role' => 'Anda tidak dapat mengubah peran akun Anda sendiri.']);
        }

        // Peran Admin Utama tidak dapat diubah menjadi CS
        if ($user->isSuperAdmin() && $data['role'] !== UserRole::Admin->value) {
            return back()->withInput()->withErrors(['role' => 'Peran Akun Admin Utama tidak dapat diubah.']);
        }

        $user->fill([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'role' => $data['role'],
            'master_cabang_id' => $data['role'] === UserRole::Cs->value ? $data['master_cabang_id'] : null,
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()
            ->route('admin.pengguna.index')
            ->with('success', "Akun {$user->name} (@{$user->username}) berhasil diperbarui.");
    }

    /**
     * Aktifkan / nonaktifkan akun (tidak boleh untuk diri sendiri)
     */
    public function toggleAktif(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Akun Admin Utama tidak dapat dinonaktifkan.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $pesan = $user->is_active
            ? "Akun {$user->name} telah diaktifkan kembali."
            : "Akun {$user->name} telah dinonaktifkan dan tidak dapat login.";

        return back()->with('success', $pesan);
    }

    /**
     * Memutus/mereset sesi aktif pengguna agar dapat login kembali di perangkat lain.
     */
    public function resetSesi(Request $request, User $user)
    {
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();
        }

        return back()->with('success', "Sesi aktif untuk {$user->name} (@{$user->username}) berhasil direset. Akun kini dapat login kembali di perangkat mana pun.");
    }

    protected function validasi(Request $request, ?User $user): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'username' => [
                'required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role' => ['required', Rule::in(UserRole::values())],
            'master_cabang_id' => [
                Rule::requiredIf(fn () => $request->input('role') === UserRole::Cs->value),
                'nullable', 'exists:master_cabangs,id',
            ],
            'password' => KataSandi::aturan($user === null),
        ];

        $pesan = [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username tersebut sudah digunakan.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, garis bawah, atau strip.',
            'username.min' => 'Username minimal :min karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email tersebut sudah digunakan.',
            'role.required' => 'Peran pengguna wajib dipilih.',
            'role.in' => 'Peran pengguna tidak valid.',
            'master_cabang_id.required' => 'Cabang penempatan wajib dipilih untuk akun CS.',
            'master_cabang_id.exists' => 'Cabang yang dipilih tidak valid.',
        ];

        return $request->validate($rules, array_merge($pesan, KataSandi::pesan()));
    }
}
