<?php

namespace App\Enums;

/**
 * Peran (role) pengguna sistem.
 * - admin : Admin Pusat (Divisi IT) — mengelola jurnal keluhan & memverifikasi pengaduan cabang.
 * - cs    : Customer Service Cabang — mengirim pengaduan nasabah & memantau statusnya.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Cs = 'cs';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin Pusat',
            self::Cs => 'Customer Service Cabang',
        };
    }

    /**
     * Nama route beranda sesuai peran (dipakai saat login / redirect).
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'dashboard',
            self::Cs => 'cs.dashboard',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
