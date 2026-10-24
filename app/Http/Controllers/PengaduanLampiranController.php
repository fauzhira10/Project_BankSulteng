<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use App\Models\PengaduanLampiran;
use App\Services\AuditTrailService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Menyajikan berkas lampiran pengaduan dari disk privat dengan format aslinya
 * (JPG / PNG / WEBP / PDF). Hanya Admin Pusat atau CS yang berwenang atas
 * pengaduan tersebut yang boleh membukanya.
 */
class PengaduanLampiranController extends Controller
{
    public function show(Pengaduan $pengaduan, PengaduanLampiran $lampiran)
    {
        Gate::authorize('view', $pengaduan);

        abort_unless((int) $lampiran->pengaduan_id === (int) $pengaduan->id, 404);

        $disk = Storage::disk(config('pengaduan.disk', 'local'));

        abort_unless($lampiran->path && $disk->exists($lampiran->path), 404, 'Berkas lampiran tidak ditemukan di penyimpanan.');

        $lampiran->setRelation('pengaduan', $pengaduan);

        // Lampiran memuat foto KTP nasabah. Siapa membukanya, kapan, dan dari
        // alamat mana termasuk yang wajib terekam — bukan hanya perubahan data.
        AuditTrailService::catatAman('lampiran.dibuka', [
            'objek' => $lampiran,
            'keterangan' => "Membuka lampiran {$lampiran->label()} pada pengaduan {$pengaduan->nomor_tiket}.",
        ]);

        return $disk->response(
            $lampiran->path,
            $lampiran->namaUnduhan(),
            ['Content-Type' => $lampiran->tipeMime()],
            'inline'
        );
    }
}
