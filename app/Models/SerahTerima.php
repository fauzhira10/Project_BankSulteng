<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerahTerima extends Model
{
    protected $table = 'serah_terimas';

    protected $fillable = [
        'nomor_dokumen',
        'tanggal_serah_terima',
        'total_tiket',
        'total_nominal',
        'tiket_ids',
        'blockchain_hash',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_serah_terima' => 'date',
            'total_tiket' => 'integer',
            'total_nominal' => 'decimal:2',
            'tiket_ids' => 'array',
        ];
    }

    /**
     * Relasi ke user / petugas admin yang menerbitkan lembar serah terima.
     */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Mengambil koleksi Jurnal yang termasuk dalam serah terima ini.
     */
    public function getDaftarJurnalAttribute(): Collection
    {
        if (empty($this->tiket_ids)) {
            return new Collection();
        }

        return Jurnal::with(['masterCabang', 'masterTransaksi', 'pengaduan'])
            ->whereIn('id', $this->tiket_ids)
            ->get();
    }

    /**
     * Format nominal dalam Rupiah.
     */
    public function totalNominalRupiah(): string
    {
        return 'Rp ' . number_format((float) $this->total_nominal, 0, ',', '.');
    }

    /**
     * Format tanggal Indonesia huruf kapital untuk dokumen resmi (contoh: 18 SEPTEMBER 2026).
     */
    public function tanggalIndonesia(): string
    {
        return strtoupper($this->tanggal_serah_terima->locale('id')->translatedFormat('d F Y'));
    }
}
