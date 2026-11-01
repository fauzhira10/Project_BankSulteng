<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DimCabang extends Model
{
    use HasFactory;

    protected $table = 'dim_cabang';

    protected $fillable = [
        'master_cabang_id',
        'kode_cabang',
        'nama_cabang',
    ];

    public function factKeluhan(): HasMany
    {
        return $this->hasMany(FactKeluhan::class, 'dim_cabang_id');
    }
}
