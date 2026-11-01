<?php

namespace App\Jobs;

use App\Services\BlockchainAuditService;
use App\Services\JangkarEthereumService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job Antrean Terdistribusi untuk Penambangan Blok Blockchain.
 *
 * Mengalihkan proses komputasi Proof of Work (PoW) yang berat dari thread HTTP web
 * ke proses pekerja mandiri di latar belakang (Background Queue Worker).
 * Komponen pembuktian mata kuliah Sistem Komputasi Terdistribusi (F09252022).
 */
class ProsesTambangBlockchainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan maksimal jika terjadi kegagalan.
     */
    public int $tries = 3;

    /**
     * Batas waktu eksekusi job dalam detik (2 menit).
     */
    public int $timeout = 120;

    /**
     * Jalankan tugas penambangan blok di worker latar belakang.
     */
    public function handle(BlockchainAuditService $blockchain, JangkarEthereumService $jangkar): void
    {
        Log::info('[Queue Worker Terdistribusi] Memulai penambangan blok blockchain di latar belakang...');

        $mulai = microtime(true);
        $baru = $blockchain->tambang();

        if (empty($baru)) {
            Log::info('[Queue Worker Terdistribusi] Tidak ada catatan audit baru yang perlu ditambang.');
            return;
        }

        $nomorBlok = array_map(fn ($b) => '#' . $b->nomor, $baru);
        $durasi = round((microtime(true) - $mulai) * 1000, 2);

        Log::info(sprintf(
            '[Queue Worker Terdistribusi] Berhasil menambang %d blok (%s) dalam %s ms.',
            count($baru),
            implode(', ', $nomorBlok),
            $durasi
        ));

        // Jika penjangkaran Ethereum aktif, jalankan penjangkaran secara asinkron
        if ($jangkar->aktif()) {
            Log::info('[Queue Worker Terdistribusi] Memulai penjangkaran blok ke smart contract Ethereum Sepolia...');
            $hasil = $jangkar->jangkarkanTertunda();
            Log::info(sprintf(
                '[Queue Worker Terdistribusi] Penjangkaran selesai: %d terkirim, galat: %s',
                $hasil['terkirim'],
                $hasil['galat'] ?: 'tidak ada'
            ));
        }
    }

    /**
     * Penanganan jika job gagal setelah seluruh percobaan habis (Toleransi Kesalahan / Fault Tolerance).
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('[Queue Worker Terdistribusi] Penambangan blok di latar belakang gagal: ' . ($exception ? $exception->getMessage() : 'Kesalahan tidak diketahui'));
    }
}
