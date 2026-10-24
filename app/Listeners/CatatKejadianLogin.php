<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;

/**
 * Menulis jejak audit untuk kejadian autentikasi yang gagal.
 *
 * Peringatan penting: event `Failed` membawa seluruh kredensial percobaan,
 * termasuk kata sandi dalam bentuk polos. Yang boleh disalin ke jejak audit
 * hanyalah username. Jangan pernah menyimpan $event->credentials apa adanya.
 */
class CatatKejadianLogin
{
    /**
     * Percobaan login dengan kredensial yang tidak cocok.
     */
    public function gagal(Failed $event): void
    {
        $username = (string) ($event->credentials['username'] ?? '-');

        AuditTrailService::catatAman('login.gagal', [
            'objek' => $event->user instanceof User ? $event->user : null,
            'keterangan' => "Percobaan masuk gagal untuk username \"{$username}\".",
        ]);
    }

    /**
     * Percobaan gagal sudah melewati batas dan akun dikunci sementara.
     */
    public function terkunci(Lockout $event): void
    {
        $username = (string) $event->request->input('username', '-');

        AuditTrailService::catatAman('login.terkunci', [
            'keterangan' => "Login untuk username \"{$username}\" dikunci sementara karena percobaan gagal beruntun.",
        ]);
    }
}
