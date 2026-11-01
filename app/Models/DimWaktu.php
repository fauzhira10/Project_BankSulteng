<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DimWaktu extends Model
{
    use HasFactory;

    protected $table = 'dim_waktu';

    protected $fillable = [
        'tanggal',
        'tahun',
        'kuartal',
        'bulan',
        'nama_bulan',
        'hari',
        'nama_hari',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tahun' => 'integer',
        'bulan' => 'integer',
        'hari' => 'integer',
    ];

    public function factKeluhan(): HasMany
    {
        return $this->hasMany(FactKeluhan::class, 'dim_waktu_id');
    }
}
