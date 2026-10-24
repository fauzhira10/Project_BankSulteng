<?php

namespace App\Console\Commands;

use App\Models\BlokAudit;
use App\Services\BlockchainAuditService;
use App\Services\JangkarEthereumService;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Memeriksa keutuhan blockchain jejak audit dan, dengan --jangkar,
 * mencocokkan hash tiap blok dengan yang tercatat di Ethereum.
 */
class PeriksaBlockchain extends Command
{
    protected $signature = 'blockchain:periksa
        {--jangkar : Cocokkan juga hash blok dengan yang tercatat di Ethereum}';

    protected $description = 'Memeriksa keutuhan blockchain jejak audit (rantai blok, proof of work, merkle root, jangkar Ethereum).';

    public function handle(BlockchainAuditService $blockchain, JangkarEthereumService $jangkar): int
    {
        $this->info('Memeriksa blockchain jejak audit...');

        $hasil = $blockchain->periksa();

        if ($hasil['jumlah_blok'] === 0) {
            $this->warn('Belum ada blok. Jalankan `php artisan blockchain:tambang` lebih dulu.');

            return self::SUCCESS;
        }

        $temuan = [];
        foreach ($hasil['blok'] as $blok) {
            foreach ($blok['masalah'] as $masalah) {
                $temuan[] = ['#'.$blok['nomor'], $masalah];
            }
        }

        if ($this->option('jangkar')) {
            if (! $jangkar->aktif()) {
                $this->warn('Penjangkaran Ethereum tidak aktif (ETH_JANGKAR_AKTIF / ETH_RPC_URL / ETH_ALAMAT_KONTRAK).');
            } else {
                try {
                    $blokTerjangkar = BlokAudit::query()
                        ->where('jangkar_status', 'terkirim')
                        ->orderBy('nomor')
                        ->get();

                    foreach ($jangkar->bandingkan($blokTerjangkar) as $nomor => $b) {
                        if ($b['status'] === 'berbeda') {
                            $temuan[] = ['#'.$nomor, "Hash blok berbeda dengan yang tercatat di Ethereum ({$b['hash_onchain']}). Blok di server telah dipalsukan."];
                        } elseif ($b['status'] === 'belum') {
                            $temuan[] = ['#'.$nomor, 'Tercatat "terkirim" di server, tetapi tidak ada di kontrak Ethereum.'];
                        }
                    }

                    $this->info($blokTerjangkar->count().' blok dicocokkan dengan Ethereum.');
                } catch (RuntimeException $e) {
                    $this->warn($e->getMessage());
                }
            }

            BlokAudit::query()->where('jangkar_status', 'konflik')->orderBy('nomor')->each(function (BlokAudit $b) use (&$temuan) {
                $temuan[] = ['#'.$b->nomor, 'Konflik jangkar: '.$b->jangkar_galat];
            });
        }

        $this->line("Blok: {$hasil['jumlah_blok']} · Catatan tersegel: {$hasil['transaksi_tercatat']} · Menunggu ditambang: {$hasil['transaksi_tertunda']}");

        if ($temuan === []) {
            $this->info('Blockchain utuh. Tidak ada blok yang berubah, hilang, atau disisipkan.');

            return self::SUCCESS;
        }

        $this->error('Blockchain TIDAK utuh. '.count($temuan).' temuan:');
        $this->table(['Blok', 'Temuan'], $temuan);
        $this->warn('Jejak audit tidak lagi dapat dipercaya sepenuhnya. Laporkan ke penanggung jawab sistem.');

        return self::FAILURE;
    }
}
