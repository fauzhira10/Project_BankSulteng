<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu blok pada blockchain jejak audit.
 *
 * Blok hanya ditulis oleh App\Services\BlockchainAuditService. Setelah
 * ditambang, kolom kepala blok (nomor s.d. hash_blok) tidak pernah diubah lagi;
 * yang boleh berubah hanya kolom jangkar_* hasil pengiriman ke Ethereum.
 */
class BlokAudit extends Model
{
    public const HASH_NOL = '0000000000000000000000000000000000000000000000000000000000000000';

    protected $fillable = [
        'nomor',
        'hash_sebelumnya',
        'merkle_root',
        'audit_id_awal',
        'audit_id_akhir',
        'jumlah_transaksi',
        'daftar_transaksi',
        'tingkat_kesulitan',
        'nonce',
        'hash_blok',
        'ditambang_pada',
        'durasi_tambang_ms',
        'jangkar_status',
        'jangkar_jaringan',
        'jangkar_tx',
        'jangkar_blok_eth',
        'jangkar_pada',
        'jangkar_galat',
    ];

    protected function casts(): array
    {
        return [
            'nomor' => 'integer',
            'audit_id_awal' => 'integer',
            'audit_id_akhir' => 'integer',
            'jumlah_transaksi' => 'integer',
            'daftar_transaksi' => 'array',
            'tingkat_kesulitan' => 'integer',
            'nonce' => 'integer',
            'durasi_tambang_ms' => 'integer',
            'jangkar_blok_eth' => 'integer',
            'ditambang_pada' => 'datetime',
            'jangkar_pada' => 'datetime',
        ];
    }

    public function isGenesis(): bool
    {
        return $this->nomor === 0;
    }

    /**
     * Kepala blok tanpa nonce — bagian yang tetap selama penambangan.
     *
     * Semua yang menentukan isi blok ikut: posisinya di rantai (nomor, hash
     * sebelumnya), isinya (merkle root, rentang, jumlah), waktu, dan tingkat
     * kesulitan. Kolom jangkar_* tidak ikut karena diisi sesudah ditambang.
     */
    public function kepala(): string
    {
        return implode('|', [
            $this->nomor,
            $this->hash_sebelumnya,
            $this->merkle_root,
            $this->audit_id_awal ?? '',
            $this->audit_id_akhir,
            $this->jumlah_transaksi,
            $this->ditambang_pada?->format('Y-m-d H:i:s'),
            $this->tingkat_kesulitan,
        ]);
    }

    public static function hashKepala(string $kepala, int $nonce): string
    {
        return hash('sha256', $kepala.'|'.$nonce);
    }

    public function hitungHash(): string
    {
        return self::hashKepala($this->kepala(), (int) $this->nonce);
    }

    /**
     * Apakah hash memenuhi syarat proof of work: diawali N angka nol.
     */
    public static function memenuhiKesulitan(string $hash, int $tingkat): bool
    {
        return str_starts_with($hash, str_repeat('0', $tingkat));
    }

    /**
     * Baris audit yang menjadi isi blok ini, menurut rentang id-nya.
     *
     * @return Builder<AuditTrail>
     */
    public function kueriTransaksi(?int $akhirBlokSebelumnya): Builder
    {
        return AuditTrail::query()
            ->where('id', '>', $akhirBlokSebelumnya ?? 0)
            ->where('id', '<=', $this->audit_id_akhir)
            ->orderBy('id');
    }

    /**
     * Daun pohon Merkle yang tersimpan saat blok ditambang.
     *
     * @return list<string>
     */
    public function daunTersimpan(): array
    {
        return array_map(static fn ($t) => (string) $t[1], $this->daftar_transaksi ?? []);
    }

    public function urlTransaksiEthereum(): ?string
    {
        return $this->jangkar_tx
            ? config('blockchain.ethereum.penjelajah').'/tx/'.$this->jangkar_tx
            : null;
    }

    public function hashPendek(?string $hash = null): string
    {
        $hash ??= $this->hash_blok;

        return substr($hash, 0, 10).'…'.substr($hash, -6);
    }
}
