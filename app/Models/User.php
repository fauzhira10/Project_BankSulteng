<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'is_superadmin', 'master_cabang_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_superadmin',
        'master_cabang_id',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_superadmin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ==================== RELASI ====================

    /**
     * Cabang penempatan (hanya untuk role CS).
     */
    public function cabang(): BelongsTo
    {
        return $this->belongsTo(MasterCabang::class, 'master_cabang_id');
    }

    /**
     * Pengaduan yang dikirim oleh pengguna ini (CS).
     */
    public function pengaduans(): HasMany
    {
        return $this->hasMany(Pengaduan::class, 'user_id');
    }

    // ==================== HELPER PERAN ====================

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && ((bool) $this->is_superadmin || $this->username === 'admin');
    }

    public function isCs(): bool
    {
        return $this->role === UserRole::Cs;
    }

    /**
     * Nama route beranda sesuai peran pengguna.
     */
    public function homeRoute(): string
    {
        return ($this->role ?? UserRole::Admin)->homeRoute();
    }

    public function labelRole(): string
    {
        return ($this->role ?? UserRole::Admin)->label();
    }
}
