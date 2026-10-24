<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPejabatTtd extends Model
{
    protected $table = 'master_pejabat_ttds';

    protected $fillable = [
        'slot',
        'nama',
        'jabatan',
        'nip',
        'ttd_image',
        'is_aktif',
        'is_default',
        'urutan',
    ];

    protected $casts = [
        'slot' => 'integer',
        'is_aktif' => 'boolean',
        'is_default' => 'boolean',
        'urutan' => 'integer',
    ];

    protected $appends = [
        'is_locked',
    ];

    public const SLOT_STAF = 1;
    public const SLOT_PEMIMPIN_UNIT = 2;
    public const SLOT_PINBAG = 3;
    public const SLOT_PEMIMPIN_DIVISI = 4;

    public const DAFTAR_SLOT = [
        self::SLOT_STAF => [
            'label' => 'Slot 1',
            'role' => 'Staf Layanan Keluhan dan Monitoring Transaksi Kartu',
            'kelompok' => 'Di Selesaikan Oleh,',
            'default_nama' => 'MUJADID',
            'default_jabatan' => 'Staf Layanan Keluhan dan Monitoring Transaksi Kartu',
        ],
        self::SLOT_PEMIMPIN_UNIT => [
            'label' => 'Slot 2',
            'role' => 'Pemimpin Unit Layanan Keluhan dan Monitoring Transaksi Kartu',
            'kelompok' => 'Di Selesaikan Oleh,',
            'default_nama' => 'AYU FEBRIANTI',
            'default_jabatan' => 'Pemimpin Unit Layanan Keluhan dan Monitoring Transaksi Kartu',
        ],
        self::SLOT_PINBAG => [
            'label' => 'Slot 3',
            'role' => 'PINBAG E- CHANNEL',
            'kelompok' => 'Di Selesaikan Oleh,',
            'default_nama' => 'WACHYUNI MADARAYU',
            'default_jabatan' => 'PINBAG E- CHANNEL',
        ],
        self::SLOT_PEMIMPIN_DIVISI => [
            'label' => 'Slot 4',
            'role' => 'Pemimpin Divisi IT',
            'kelompok' => 'Di ketahui Oleh,',
            'default_nama' => 'DIANA, ST',
            'default_jabatan' => 'Pemimpin Divisi IT',
        ],
    ];

    public function labelSlot(): string
    {
        return self::DAFTAR_SLOT[$this->slot]['label'] ?? "Slot {$this->slot}";
    }

    public function kelompokSlot(): string
    {
        return self::DAFTAR_SLOT[$this->slot]['kelompok'] ?? 'Di Selesaikan Oleh,';
    }

    /**
     * Memeriksa apakah pejabat ini merupakan salah satu dari 4 pejabat default sistem:
     * MUJADID, AYU FEBRIANTI, WACHYUNI MADARAYU, DIANA, ST.
     * Pejabat default sistem tidak boleh dihapus, dan nama serta jabatannya dikunci (hanya TTD yang bisa diubah).
     */
    public function isDefaultMaster(): bool
    {
        $defaultNames = [
            1 => 'MUJADID',
            2 => 'AYU FEBRIANTI',
            3 => 'WACHYUNI MADARAYU',
            4 => 'DIANA, ST',
        ];

        if (!isset($defaultNames[$this->slot])) {
            return false;
        }

        $cleanNama = strtoupper(preg_replace('/[^A-Z]/', '', $this->nama ?? ''));
        $cleanTarget = strtoupper(preg_replace('/[^A-Z]/', '', $defaultNames[$this->slot]));

        return $cleanNama === $cleanTarget;
    }

    /**
     * Accessor untuk mengecek status lock nama & jabatan di frontend/API
     */
    public function getIsLockedAttribute(): bool
    {
        return $this->isDefaultMaster();
    }

    /**
     * Menentukan apakah data pejabat ini boleh dihapus dari sistem.
     */
    public function isDeletable(): bool
    {
        return !$this->isDefaultMaster();
    }

    /**
     * Mengambil seluruh pejabat default untuk slot 1 - 4
     *
     * @return array<int, self>
     */
    public static function getAllDefault(): array
    {
        $defaults = [];
        for ($s = 1; $s <= 4; $s++) {
            $pejabat = self::where('slot', $s)
                ->where('is_aktif', true)
                ->orderByDesc('is_default')
                ->orderBy('urutan')
                ->orderBy('id')
                ->first();

            if ($pejabat) {
                $defaults[$s] = $pejabat;
            }
        }

        return $defaults;
    }

    /**
     * Mengambil daftar pejabat aktif dikelompokkan berdasarkan slot (1-4)
     *
     * @return array<int, \Illuminate\Database\Eloquent\Collection>
     */
    public static function getAktifGrouped(): array
    {
        $grouped = [];
        for ($s = 1; $s <= 4; $s++) {
            $grouped[$s] = self::where('slot', $s)
                ->where('is_aktif', true)
                ->orderByDesc('is_default')
                ->orderBy('urutan')
                ->orderBy('nama')
                ->get();
        }

        return $grouped;
    }
}
