<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lampiran pengaduan. Berkas disimpan di disk privat apa adanya,
 * sesuai format yang dikirim CS (JPG / PNG / WEBP / PDF).
 */
class PengaduanLampiran extends Model
{
    /**
     * `pengaduan_id` tidak perlu disebut: nilainya diisi relasi hasMany,
     * bukan lewat pengisian massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'jenis',
        'nama_asli',
        'path',
        'sumber',
        'mime',
        'ukuran',
    ];

    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class, 'pengaduan_id');
    }

    /**
     * Label jenis lampiran dari konfigurasi (mis. "Foto KTP Nasabah").
     */
    public function label(): string
    {
        return config("pengaduan.jenis_lampiran.{$this->jenis}.label", ucwords(str_replace('_', ' ', (string) $this->jenis)));
    }

    /**
     * Ekstensi berkas asli (huruf kecil, tanpa titik).
     */
    public function ekstensi(): string
    {
        return strtolower(pathinfo((string) $this->path, PATHINFO_EXTENSION)) ?: 'file';
    }

    /**
     * Label format untuk ditampilkan pada badge (JPG, PNG, WEBP, PDF).
     */
    public function labelFormat(): string
    {
        return strtoupper($this->ekstensi());
    }

    public function adalahGambar(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }

    public function ikon(): string
    {
        return $this->adalahGambar() ? '🖼️' : '📄';
    }

    /**
     * Tipe MIME untuk header respons (dengan cadangan bila kolom kosong).
     */
    public function tipeMime(): string
    {
        return $this->mime ?: match ($this->ekstensi()) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/pdf',
        };
    }

    /**
     * Ukuran file dalam format mudah dibaca.
     */
    public function ukuranTerbaca(): string
    {
        $byte = (int) $this->ukuran;

        if ($byte >= 1048576) {
            return number_format($byte / 1048576, 2, ',', '.').' MB';
        }
        if ($byte >= 1024) {
            return number_format($byte / 1024, 0, ',', '.').' KB';
        }

        return $byte.' B';
    }

    /**
     * Nama berkas saat dibuka/diunduh, mempertahankan ekstensi asli.
     * Contoh: "Foto KTP Nasabah - PGD-001-20260904-0001.jpg"
     *
     * Karakter yang tidak sah pada nama berkas dibersihkan lebih dulu. Header
     * Content-Disposition menolak "/" dan "\" (label "Kartu ATM / Debit" pernah
     * memicu galat), sedangkan : * ? " < > | tidak sah sebagai nama berkas Windows.
     */
    public function namaUnduhan(): string
    {
        $nomor = $this->pengaduan?->nomor_tiket ?? 'lampiran';
        $nama = $this->label().' - '.$nomor.'.'.$this->ekstensi();

        $nama = preg_replace('#[/\\\\:*?"<>|]+#', '-', $nama);
        $nama = preg_replace('/\s+/', ' ', $nama);

        return trim($nama, " .-\t\n\r\0\x0B") ?: 'lampiran.'.$this->ekstensi();
    }
}
