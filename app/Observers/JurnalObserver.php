<?php

namespace App\Observers;

use App\Enums\PengaduanStatus;
use App\Models\Jurnal;
use App\Models\Pengaduan;
use App\Services\AuditTrailService;
use Illuminate\Support\Carbon;

/**
 * Menyinkronkan status pengaduan CS cabang setiap kali jurnal pusat berubah,
 * sekaligus menulis jejak audit atas setiap pembuatan, perubahan, dan penghapusan.
 *
 * Catatan: observer tidak terpicu pada mass delete (mis. Jurnal::query()->delete()
 * di resetAllData) — kasus tersebut ditangani langsung di controller.
 */
class JurnalObserver
{
    /**
     * Jejak audit pembuatan jurnal: seluruh isi baris disimpan sebagai nilai baru.
     */
    public function created(Jurnal $jurnal): void
    {
        AuditTrailService::catat('jurnal.dibuat', [
            'objek' => $jurnal,
            'baru' => static::atributTerpantau($jurnal->getAttributes()),
            'keterangan' => "Jurnal keluhan atas nama {$jurnal->nama_nasabah} (No. Resi {$jurnal->no_resi}) dibuat.",
        ]);
    }

    /**
     * Jejak audit perubahan: hanya kolom yang benar-benar berubah yang dicatat,
     * lengkap dengan nilai sebelum dan sesudahnya.
     */
    public function updated(Jurnal $jurnal): void
    {
        $berubah = static::atributTerpantau($jurnal->getChanges());

        if ($berubah === []) {
            return;
        }

        $sebelum = [];
        foreach (array_keys($berubah) as $kolom) {
            $sebelum[$kolom] = $jurnal->getOriginal($kolom);
        }

        AuditTrailService::catat('jurnal.diubah', [
            'objek' => $jurnal,
            'lama' => $sebelum,
            'baru' => $berubah,
            'keterangan' => "Jurnal keluhan atas nama {$jurnal->nama_nasabah} diubah pada kolom: ".implode(', ', array_keys($berubah)).'.',
        ]);
    }

    /**
     * Jejak audit penghapusan: seluruh isi baris disimpan, sebab setelah ini
     * barisnya tidak ada lagi dan hanya jejak inilah rekamannya.
     *
     * Relasi jurnal_id sengaja tidak diisi (jurnalnya akan hilang); penautan
     * dilakukan lewat auditable_id yang tidak terikat foreign key.
     */
    public function deleted(Jurnal $jurnal): void
    {
        AuditTrailService::catat('jurnal.dihapus', [
            'objek' => $jurnal,
            'jurnal' => null,
            'lama' => static::atributTerpantau($jurnal->getOriginal()),
            'keterangan' => "Jurnal keluhan atas nama {$jurnal->nama_nasabah} (No. Resi {$jurnal->no_resi}) dihapus permanen.",
        ]);
    }

    /**
     * Buang kolom yang tidak menambah makna audit (id & stempel waktu Eloquent).
     *
     * @param  array<string, mixed>  $atribut
     * @return array<string, mixed>
     */
    protected static function atributTerpantau(array $atribut): array
    {
        return array_diff_key($atribut, array_flip(['id', 'created_at', 'updated_at']));
    }

    public function saved(Jurnal $jurnal): void
    {
        $pengaduan = Pengaduan::where('jurnal_id', $jurnal->id)->first();

        if ($pengaduan) {
            static::sinkronkan($pengaduan, $jurnal);
        }
    }

    public function deleting(Jurnal $jurnal): void
    {
        $pengaduan = Pengaduan::where('jurnal_id', $jurnal->id)->first();

        if ($pengaduan) {
            static::lepasTautan($pengaduan);
        }
    }

    /**
     * Turunkan status pengaduan dari status jurnal (Done/Success → Selesai,
     * Rejected → Ditolak, lainnya → Diproses). Status jurnal yang diketik manual
     * di luar daftar baku ikut dipetakan ke "Diproses".
     */
    public static function sinkronkan(Pengaduan $pengaduan, Jurnal $jurnal): void
    {
        $statusBaru = PengaduanStatus::dariStatusJurnal($jurnal->status);

        $data = [
            'jurnal_id' => $jurnal->id,
            'status' => $statusBaru,
            'diproses_at' => $pengaduan->diproses_at ?? now(),
            'diterima_at' => $pengaduan->diterima_at ?? now(),
        ];

        if ($statusBaru === PengaduanStatus::Selesai) {
            $tglSelesai = $jurnal->tgl_selesai ? Carbon::parse($jurnal->tgl_selesai) : now();
            $data['selesai_at'] = $pengaduan->selesai_at ?? $tglSelesai;
            $data['ditolak_at'] = null;
        } elseif ($statusBaru === PengaduanStatus::Ditolak) {
            $data['ditolak_at'] = $pengaduan->ditolak_at ?? now();
            $data['selesai_at'] = null;
            if (empty(trim((string) $pengaduan->catatan_pusat))) {
                $data['catatan_pusat'] = 'Klaim ditolak berdasarkan hasil pemeriksaan jurnal keluhan pusat.';
            }
        } else {
            $data['selesai_at'] = null;
            $data['ditolak_at'] = null;
        }

        $pengaduan->fill($data)->save();
    }

    /**
     * Jurnal dihapus → pengaduan kembali ke status Diterima (menunggu dijurnal ulang).
     */
    public static function lepasTautan(Pengaduan $pengaduan): void
    {
        $pengaduan->fill([
            'jurnal_id' => null,
            'status' => PengaduanStatus::Diterima,
            'diproses_at' => null,
            'selesai_at' => null,
            'ditolak_at' => null,
        ])->save();
    }
}
