<?php

namespace App\Jobs;

use App\Services\DataWarehouseEtlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job Antrean Terdistribusi untuk Ekstraksi, Transformasi, dan Pemuatan (ETL) Data Warehouse.
 *
 * Mengalihkan proses agregasi data besar dari thread HTTP ke antrean pekerja latar belakang.
 */
class ProsesEtlDataWarehouseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180;

    public function __construct(
        public bool $fresh = false
    ) {}

    public function handle(DataWarehouseEtlService $etlService): void
    {
        Log::info('[Queue Worker Terdistribusi] Memulai pipeline ETL Data Warehouse di latar belakang...');

        $hasil = $etlService->jalankanEtl(null, $this->fresh);

        Log::info(sprintf(
            '[Queue Worker Terdistribusi] ETL Selesai: %d data diekstrak, %d baris fakta dimuat dalam %s ms.',
            $hasil['total_ekstrak'],
            $hasil['total_fakta'],
            $hasil['durasi_ms']
        ));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('[Queue Worker Terdistribusi] ETL Data Warehouse di latar belakang gagal: ' . ($exception ? $exception->getMessage() : 'Kesalahan tidak diketahui'));
    }
}
