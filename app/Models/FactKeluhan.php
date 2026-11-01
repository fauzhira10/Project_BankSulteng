<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactKeluhan extends Model
{
    use HasFactory;

    protected $table = 'fact_keluhan';

    protected $fillable = [
        'jurnal_id',
        'dim_waktu_id',
        'dim_cabang_id',
        'dim_transaksi_id',
        'status',
        'is_selesai',
        'nominal_transaksi',
        'biaya_admin',
        'durasi_penyelesaian_hari',
    ];

    protected $casts = [
        'jurnal_id' => 'integer',
        'dim_waktu_id' => 'integer',
        'dim_cabang_id' => 'integer',
        'dim_transaksi_id' => 'integer',
        'is_selesai' => 'boolean',
        'nominal_transaksi' => 'float',
        'biaya_admin' => 'float',
        'durasi_penyelesaian_hari' => 'integer',
    ];

    public function dimWaktu(): BelongsTo
    {
        return $this->belongsTo(DimWaktu::class, 'dim_waktu_id');
    }

    public function dimCabang(): BelongsTo
    {
        return $this->belongsTo(DimCabang::class, 'dim_cabang_id');
    }

    public function dimTransaksi(): BelongsTo
    {
        return $this->belongsTo(DimTransaksi::class, 'dim_transaksi_id');
    }

    public function jurnal(): BelongsTo
    {
        return $this->belongsTo(Jurnal::class, 'jurnal_id');
    }
}
