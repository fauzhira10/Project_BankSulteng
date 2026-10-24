<?php

namespace App\Enums;

/**
 * Siklus status pengaduan nasabah yang dikirim CS cabang ke pusat.
 *
 * Terkirim  → CS baru mengirim, menunggu verifikasi pusat (CS masih boleh edit/hapus)
 * Diterima  → Pusat sudah menerima/meninjau, belum dimasukkan ke jurnal (data terkunci)
 * Diproses  → Sudah dimasukkan ke jurnal keluhan (jurnal_id terisi)
 * Selesai   → Status jurnal Done / Success
 * Ditolak   → Ditolak pusat (wajib catatan) atau status jurnal Rejected
 */
enum PengaduanStatus: string
{
    case Terkirim = 'Terkirim';
    case Diterima = 'Diterima';
    case Diproses = 'Diproses';
    case Selesai = 'Selesai';
    case Ditolak = 'Ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Terkirim => 'Menunggu Verifikasi Pusat',
            self::Diterima => 'Diterima Pusat',
            self::Diproses => 'Dalam Proses Jurnal',
            self::Selesai => 'Selesai',
            self::Ditolak => 'Ditolak',
        };
    }

    public function labelSingkat(): string
    {
        return $this->value;
    }

    public function deskripsi(): string
    {
        return match ($this) {
            self::Terkirim => 'Pengaduan sudah terkirim dan menunggu diverifikasi oleh Admin Pusat (Divisi IT). Data masih dapat Anda ubah.',
            self::Diterima => 'Pengaduan telah diterima Admin Pusat dan sedang menunggu dimasukkan ke dalam Jurnal Keluhan. Data sudah terkunci.',
            self::Diproses => 'Pengaduan sudah tercatat di Jurnal Keluhan pusat dan sedang dalam proses penanganan.',
            self::Selesai => 'Penanganan keluhan telah dinyatakan selesai oleh Admin Pusat.',
            self::Ditolak => 'Pengaduan ditolak. Silakan lihat catatan dari pusat untuk keterangan lebih lanjut.',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Terkirim => 'badge-terkirim',
            self::Diterima => 'badge-diterima',
            self::Diproses => 'badge-diproses',
            self::Selesai => 'badge-selesai',
            self::Ditolak => 'badge-ditolak',
        };
    }

    public function ikon(): string
    {
        return match ($this) {
            self::Terkirim => '🟡',
            self::Diterima => '🔵',
            self::Diproses => '🟣',
            self::Selesai => '🟢',
            self::Ditolak => '🔴',
        };
    }

    /**
     * Apakah CS masih boleh mengubah/menghapus pengaduan pada status ini.
     */
    public function bisaDiedit(): bool
    {
        return $this === self::Terkirim;
    }

    /**
     * Pemetaan status jurnal pusat (-, Menunggu, Success, Done, Rejected) ke status pengaduan.
     */
    public static function dariStatusJurnal(?string $statusJurnal): self
    {
        $st = strtolower(trim((string) $statusJurnal));

        return match (true) {
            in_array($st, ['done', 'success'], true) => self::Selesai,
            $st === 'rejected' => self::Ditolak,
            default => self::Diproses,
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
