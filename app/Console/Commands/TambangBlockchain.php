<?php

namespace App\Console\Commands;

use App\Services\BlockchainAuditService;
use App\Services\JangkarEthereumService;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Menambang catatan audit yang belum masuk blok, lalu (bila diaktifkan)
 * menjangkarkan hash blok-blok baru ke Ethereum.
 *
 * Dijadwalkan tiap sepuluh menit di routes/console.php.
 */
class TambangBlockchain extends Command
{
    protected $signature = 'blockchain:tambang
        {--penuh : Hanya bentuk blok yang sudah penuh; sisa catatan menunggu putaran berikutnya}
        {--tanpa-jangkar : Jangan kirim ke Ethereum meskipun ETH_JANGKAR_AKTIF=true}';

    protected $description = 'Menambang jejak audit menjadi blok blockchain dan menjangkarkannya ke Ethereum.';

    public function handle(BlockchainAuditService $blockchain, JangkarEthereumService $jangkar): int
    {
        try {
            $baru = $blockchain->tambang(! $this->option('penuh'));
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($baru === []) {
            $this->info('Tidak ada catatan audit baru untuk ditambang.');
        } else {
            $this->table(
                ['Blok', 'Transaksi', 'Nonce', 'Hash Blok', 'Waktu Tambang'],
                array_map(fn ($b) => [
                    '#'.$b->nomor,
                    $b->jumlah_transaksi,
                    number_format($b->nonce, 0, ',', '.'),
                    $b->hash_blok,
                    $b->durasi_tambang_ms.' ms',
                ], $baru)
            );
            $this->info(count($baru).' blok baru ditambang.');
        }

        if ($this->option('tanpa-jangkar') || ! $jangkar->aktif()) {
            return self::SUCCESS;
        }

        $this->info('Menjangkarkan blok ke Ethereum...');
        $hasil = $jangkar->jangkarkanTertunda();

        if ($hasil['terkirim'] > 0) {
            $this->info("{$hasil['terkirim']} blok berhasil dijangkarkan.");
        }

        if ($hasil['galat']) {
            $this->warn('Penjangkaran terhenti — '.$hasil['galat']);

            if (! $hasil['konflik']) {
                $this->warn('Blok yang gagal akan dicoba lagi pada putaran berikutnya.');

                return self::SUCCESS;
            }

            // Konflik tidak pernah dicoba ulang: kontrak menolak menimpa hash lama,
            // dan perbedaannya sendiri adalah temuan yang harus ditindaklanjuti.
            $this->error('Hash blok di server berbeda dengan yang tercatat di Ethereum. Jalankan `php artisan blockchain:periksa --jangkar`.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
