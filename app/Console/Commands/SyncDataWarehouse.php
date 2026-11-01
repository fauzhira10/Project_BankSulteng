<?php

namespace App\Console\Commands;

use App\Services\DataWarehouseEtlService;
use Illuminate\Console\Command;

class SyncDataWarehouse extends Command
{
    /**
     * Nama dan signature perintah console.
     *
     * @var string
     */
    protected $signature = 'dw:sync {--fresh : Kosongkan seluruh tabel data warehouse sebelum sinkronisasi}';

    /**
     * Deskripsi perintah console.
     *
     * @var string
     */
    protected $description = 'Jalankan pipeline ETL (Extract, Transform, Load) untuk sinkronisasi Data Warehouse E-Jurnal Keluhan';

    /**
     * Eksekusi perintah console.
     */
    public function handle(DataWarehouseEtlService $etlService): int
    {
        $fresh = $this->option('fresh');

        $this->info('========================================================');
        $this->info('  PIPELINE ETL DATA WAREHOUSE - PT BANK SULTENG');
        $this->info('========================================================');

        if ($fresh) {
            $this->warn('Mode --fresh aktif: Tabel fakta dan dimensi akan dikosongkan terlebih dahulu.');
        }

        $this->line('Memulai proses ETL (Extract -> Transform -> Load)...');

        $progressBar = null;

        $hasil = $etlService->jalankanEtl(function (int $current, int $total, string $step) use (&$progressBar) {
            if ($progressBar === null) {
                $progressBar = $this->output->createProgressBar(100);
                $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
            }
            $progressBar->setMessage($step);
            $progressBar->setProgress($current);
        }, $fresh);

        if ($progressBar) {
            $progressBar->finish();
            $this->newLine(2);
        }

        $this->info('✓ Pipeline ETL Berhasil Dieksekusi!');
        $this->newLine();

        $this->table(
            ['Metrik Pipeline ETL', 'Nilai / Hasil'],
            [
                ['Total Data Transaksi Diekstrak (Extract)', number_format($hasil['total_ekstrak'], 0, ',', '.')],
                ['Total Baris Fakta Dimuat (Load Fact)', number_format($hasil['total_fakta'], 0, ',', '.')],
                ['Dimensi Waktu Tercatat (Dim Waktu)', number_format($hasil['dim_waktu_baru'], 0, ',', '.')],
                ['Dimensi Cabang Terdata (Dim Cabang)', number_format($hasil['dim_cabang_baru'], 0, ',', '.')],
                ['Dimensi Transaksi Terdata (Dim Transaksi)', number_format($hasil['dim_transaksi_baru'], 0, ',', '.')],
                ['Waktu Eksekusi ETL', $hasil['durasi_ms'] . ' ms'],
            ]
        );

        $stats = $etlService->getStatistikDataWarehouse();

        $this->newLine();
        $this->info('Ringkasan Gudang Data (Data Mart Perbankan):');
        $this->table(
            ['Parameter Analitik', 'Nilai'],
            [
                ['Total Kasus Keluhan di Gudang Data', number_format($stats['total_fakta'], 0, ',', '.')],
                ['Kasus Selesai (Success / Done)', number_format($stats['total_selesai'], 0, ',', '.')],
                ['Kasus Masih Dalam Proses (Pending)', number_format($stats['total_pending'], 0, ',', '.')],
                ['Total Perputaran Nominal Uang Klaim', 'Rp ' . number_format($stats['total_nominal_transaksi'], 2, ',', '.')],
                ['Total Biaya Administrasi', 'Rp ' . number_format($stats['total_biaya_admin'], 2, ',', '.')],
                ['Rata-rata Waktu Penyelesaian', $stats['rata_rata_durasi_hari'] . ' Hari'],
            ]
        );

        $this->info('Data Warehouse siap digunakan untuk analisis Business Intelligence.');

        return self::SUCCESS;
    }
}
