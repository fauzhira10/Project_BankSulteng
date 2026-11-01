<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DimTransaksi extends Model
{
    use HasFactory;

    protected $table = 'dim_transaksi';

    protected $fillable = [
        'master_transaksi_id',
        'jenis_transaksi',
        'channel',
    ];

    public function factKeluhan(): HasMany
    {
        return $this->hasMany(FactKeluhan::class, 'dim_transaksi_id');
    }
}
