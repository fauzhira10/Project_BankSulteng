<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Jurnal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Penulis tunggal jejak audit. Jangan menulis ke tabel audit_trails dari tempat lain.
 *
 * Setiap baris menyegel hash baris sebelumnya, jadi urutan penulisan menentukan
 * keutuhan rantai. Karena itu pembacaan baris terakhir dan penulisan baris baru
 * harus berada dalam satu transaksi dengan lockForUpdate — pola yang sama dengan
 * NomorTiketService, dan dengan alasan yang sama: dua permintaan bersamaan tidak
 * boleh menyegel hash sebelumnya yang identik.
 */
class AuditTrailService
{
    /**
     * Catat satu kejadian.
     *
     * @param  array{
     *     objek?: Model|null,
     *     jurnal?: Jurnal|null,
     *     lama?: array<string, mixed>|null,
     *     baru?: array<string, mixed>|null,
     *     keterangan?: string|null,
     *     pelaku?: User|null
     * }  $opsi
     */
    public static function catat(string $aksi, array $opsi = []): AuditTrail
    {
        $objek = $opsi['objek'] ?? null;

        // array_key_exists, bukan ??: pemanggil harus bisa memaksa jurnal_id tetap
        // kosong dengan mengirim 'jurnal' => null. Itu yang dipakai saat mencatat
        // penghapusan jurnal — barisnya sudah tidak ada, sehingga mengisi foreign
        // key ke sana pasti gagal.
        $jurnal = array_key_exists('jurnal', $opsi)
            ? $opsi['jurnal']
            : ($objek instanceof Jurnal ? $objek : null);
        $pelaku = $opsi['pelaku'] ?? Auth::user();
        $permintaan = request();

        // Detik bulat: kolom timestamp MySQL tidak menyimpan mikrodetik, sehingga
        // nilai yang dibaca ulang harus sama persis dengan yang ikut dihash.
        $waktu = now()->startOfSecond();

        return DB::transaction(function () use ($aksi, $objek, $jurnal, $pelaku, $permintaan, $opsi, $waktu) {
            $terakhir = AuditTrail::query()
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $hashSebelumnya = $terakhir?->hash_sekarang;

            $audit = new AuditTrail([
                'user_id' => $pelaku?->id,
                'username' => $pelaku?->username,
                'aksi' => $aksi,
                'auditable_type' => $objek ? $objek::class : null,
                'auditable_id' => $objek?->getKey(),
                'jurnal_id' => $jurnal?->getKey(),
                'nilai_lama' => $opsi['lama'] ?? null,
                'nilai_baru' => $opsi['baru'] ?? null,
                'keterangan' => $opsi['keterangan'] ?? null,
                'ip' => $permintaan?->ip(),
                'user_agent' => mb_substr((string) $permintaan?->userAgent(), 0, 512) ?: null,
            ]);

            $audit->created_at = $waktu;
            $audit->updated_at = $waktu;
            $audit->hash_sebelumnya = $hashSebelumnya;
            $audit->hash_sekarang = $audit->hitungHash($hashSebelumnya);
            $audit->save();

            return $audit;
        });
    }

    /**
     * Versi yang tidak pernah melempar galat.
     *
     * Dipakai pada jalur yang hanya membaca atau tidak mengubah data (membuka
     * lampiran, mencatat login gagal): kegagalan menulis jejak di situ tidak boleh
     * menggagalkan permintaan pengguna. Untuk perubahan data, pakai catat() biasa
     * supaya kegagalan audit ikut membatalkan perubahannya.
     */
    public static function catatAman(string $aksi, array $opsi = []): ?AuditTrail
    {
        try {
            return static::catat($aksi, $opsi);
        } catch (Throwable $e) {
            Log::warning('Gagal menulis jejak audit.', [
                'aksi' => $aksi,
                'galat' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Telusuri seluruh rantai hash dari baris pertama.
     *
     * @return array{jumlah:int, utuh:bool, masalah:array<int, array{id:int, sebab:string}>}
     */
    public static function periksaRantai(): array
    {
        $masalah = [];
        $jumlah = 0;
        $hashSebelumnya = null;

        foreach (AuditTrail::query()->orderBy('id')->cursor() as $baris) {
            $jumlah++;

            if (($baris->hash_sebelumnya ?? null) !== $hashSebelumnya) {
                $masalah[] = [
                    'id' => (int) $baris->id,
                    'sebab' => 'Rantai terputus: ada baris yang dihapus atau disisipkan sebelum baris ini.',
                ];
            } elseif (! $baris->hashCocok()) {
                $masalah[] = [
                    'id' => (int) $baris->id,
                    'sebab' => 'Isi baris tidak sesuai hash: data audit diubah setelah ditulis.',
                ];
            }

            $hashSebelumnya = $baris->hash_sekarang;
        }

        return [
            'jumlah' => $jumlah,
            'utuh' => $masalah === [],
            'masalah' => $masalah,
        ];
    }
}
