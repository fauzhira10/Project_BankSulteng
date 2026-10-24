<?php

namespace App\Policies;

use App\Enums\PengaduanStatus;
use App\Models\Pengaduan;
use App\Models\User;

/**
 * Aturan akses pengaduan nasabah:
 * - Admin Pusat  : boleh melihat semua, menerima, dan menolak.
 * - CS Cabang    : pengaduan yang ia kirim sendiri, yang asal cabangnya = cabang akunnya,
 *                  atau yang dikirim CS lain dari cabang akun yang sama.
 *                  Edit/hapus hanya saat status masih Terkirim.
 */
class PengaduanPolicy
{
    /**
     * Apakah pengaduan berada dalam lingkup CS yang login (lihat Pengaduan::scopeUntukCs).
     */
    protected function dalamLingkupCs(User $user, Pengaduan $pengaduan): bool
    {
        if (! $user->isCs()) {
            return false;
        }

        if ((int) $pengaduan->user_id === (int) $user->id) {
            return true;
        }

        $cabangId = $user->master_cabang_id;
        if ($cabangId === null) {
            return false;
        }

        if ((int) $pengaduan->master_cabang_id === (int) $cabangId) {
            return true;
        }

        $pengirim = $pengaduan->relationLoaded('user') ? $pengaduan->user : $pengaduan->user()->first();

        return $pengirim !== null && (int) $pengirim->master_cabang_id === (int) $cabangId;
    }

    public function view(User $user, Pengaduan $pengaduan): bool
    {
        return $user->isAdmin() || $this->dalamLingkupCs($user, $pengaduan);
    }

    public function create(User $user): bool
    {
        return $user->isCs();
    }

    public function update(User $user, Pengaduan $pengaduan): bool
    {
        return $this->dalamLingkupCs($user, $pengaduan) && $pengaduan->bisaDiedit();
    }

    public function delete(User $user, Pengaduan $pengaduan): bool
    {
        return $this->dalamLingkupCs($user, $pengaduan) && $pengaduan->bisaDiedit();
    }

    /**
     * Admin menerima pengaduan (Terkirim → Diterima).
     */
    public function terima(User $user, Pengaduan $pengaduan): bool
    {
        return $user->isAdmin() && $pengaduan->status === PengaduanStatus::Terkirim;
    }

    /**
     * Admin menolak pengaduan sebelum masuk jurnal.
     */
    public function tolak(User $user, Pengaduan $pengaduan): bool
    {
        return $user->isAdmin()
            && in_array($pengaduan->status, [PengaduanStatus::Terkirim, PengaduanStatus::Diterima], true);
    }

    /**
     * Admin memasukkan pengaduan ke jurnal keluhan.
     */
    public function jurnalkan(User $user, Pengaduan $pengaduan): bool
    {
        return $user->isAdmin()
            && $pengaduan->jurnal_id === null
            && in_array($pengaduan->status, [PengaduanStatus::Terkirim, PengaduanStatus::Diterima], true);
    }
}
