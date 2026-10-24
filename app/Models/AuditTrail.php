<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris jejak audit.
 *
 * Baris tidak pernah diubah atau dihapus setelah ditulis. Keutuhannya dijaga
 * rantai hash: setiap baris ikut menyegel hash baris sebelumnya, sehingga
 * menyunting atau membuang satu baris akan memutus rantai pada baris sesudahnya
 * dan terdeteksi oleh `php artisan audit:periksa`.
 */
class AuditTrail extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'aksi',
        'auditable_type',
        'auditable_id',
        'jurnal_id',
        'nilai_lama',
        'nilai_baru',
        'keterangan',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'nilai_lama' => 'array',
            'nilai_baru' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jurnal(): BelongsTo
    {
        return $this->belongsTo(Jurnal::class, 'jurnal_id');
    }

    /**
     * Ringkasan isi baris dalam bentuk yang selalu sama, dipakai sebagai bahan hash.
     *
     * `user_agent` sengaja tidak ikut disegel: nilainya panjang, dipotong kolom
     * database, dan tidak menentukan makna kejadian. Kolom yang menentukan siapa
     * melakukan apa terhadap data apa semuanya ikut.
     *
     * `jurnal_id` juga tidak disegel, dan ini penting: foreign key-nya memakai
     * nullOnDelete, jadi basis data SENDIRI yang mengosongkan kolom itu pada
     * baris jurnal.dibuat/jurnal.diubah begitu jurnalnya dihapus. Menyegelnya
     * berarti setiap penghapusan jurnal langsung memutus rantai — tepat pada
     * kejadian yang paling perlu dipercaya. Tautan ke jurnalnya tetap tersegel
     * lewat `auditable_type` + `auditable_id`, yang tidak punya foreign key dan
     * karena itu tidak pernah ikut diubah basis data; `jurnal_id` hanya jalan
     * pintas untuk menelusuri riwayat satu jurnal.
     *
     * `nilai_lama` dan `nilai_baru` diurutkan kuncinya lebih dulu. MySQL menyimpan
     * kolom bertipe JSON dalam bentuk binernya sendiri dan MENGURUTKAN ULANG kunci
     * objek (menurut panjang, lalu abjad), sehingga array yang dibaca kembali tidak
     * pernah persis sama urutannya dengan yang ditulis. Tanpa pengurutan ini seluruh
     * baris yang memuat lebih dari satu kolom akan dilaporkan "diubah" oleh
     * `audit:periksa` padahal tidak ada yang menyentuhnya — dan alarm palsu yang
     * selalu berbunyi sama saja dengan tidak ada alarm. SQLite menyimpan JSON apa
     * adanya sebagai teks, jadi masalah ini tidak muncul di test.
     */
    public function sidikJari(): string
    {
        return json_encode([
            'user_id' => $this->user_id,
            'username' => $this->username,
            'aksi' => $this->aksi,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id !== null ? (int) $this->auditable_id : null,
            'nilai_lama' => self::urutkanKunci($this->nilai_lama),
            'nilai_baru' => self::urutkanKunci($this->nilai_baru),
            'keterangan' => $this->keterangan,
            'ip' => $this->ip,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Urutkan kunci array secara rekursif agar sidik jarinya tidak bergantung
     * pada urutan penyimpanan basis data.
     *
     * Array berindeks angka dibiarkan apa adanya: urutannya adalah bagian dari
     * isinya, bukan kebetulan penyimpanan.
     */
    protected static function urutkanKunci(mixed $nilai): mixed
    {
        if (! is_array($nilai)) {
            return $nilai;
        }

        $nilai = array_map(static fn ($isi) => self::urutkanKunci($isi), $nilai);

        if (! array_is_list($nilai)) {
            ksort($nilai);
        }

        return $nilai;
    }

    /**
     * Hitung ulang hash baris ini berdasarkan hash baris sebelumnya.
     */
    public function hitungHash(?string $hashSebelumnya): string
    {
        return hash('sha256', ($hashSebelumnya ?? '').'|'.$this->sidikJari());
    }

    /**
     * Apakah isi baris ini masih sesuai dengan hash yang tersimpan.
     */
    public function hashCocok(): bool
    {
        return hash_equals((string) $this->hash_sekarang, $this->hitungHash($this->hash_sebelumnya));
    }
}
