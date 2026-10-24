<?php

namespace App\Console\Commands;

use App\Services\AuditTrailService;
use Illuminate\Console\Command;

/**
 * Memeriksa keutuhan rantai hash jejak audit.
 *
 * Rantai hash hanya bermanfaat bila benar-benar diperiksa. Jalankan perintah ini
 * secara berkala (mis. terjadwal harian) agar penyuntingan langsung ke tabel
 * audit_trails lewat phpMyAdmin atau klien SQL lain ikut ketahuan.
 */
class PeriksaAuditTrail extends Command
{
    protected $signature = 'audit:periksa';

    protected $description = 'Memeriksa keutuhan rantai hash pada tabel audit_trails.';

    public function handle(): int
    {
        $this->info('Memeriksa rantai jejak audit...');

        $hasil = AuditTrailService::periksaRantai();

        if ($hasil['jumlah'] === 0) {
            $this->warn('Tabel audit_trails masih kosong — belum ada yang dapat diperiksa.');

            return self::SUCCESS;
        }

        if ($hasil['utuh']) {
            $this->info("Rantai utuh. {$hasil['jumlah']} baris jejak audit diperiksa, tidak ada yang berubah atau hilang.");

            return self::SUCCESS;
        }

        $this->error("Rantai TIDAK utuh. {$hasil['jumlah']} baris diperiksa, ".count($hasil['masalah']).' baris bermasalah:');
        $this->newLine();

        $this->table(
            ['ID Baris', 'Temuan'],
            array_map(fn ($m) => [$m['id'], $m['sebab']], $hasil['masalah'])
        );

        $this->newLine();
        $this->warn('Jejak audit tidak lagi dapat dipercaya sepenuhnya. Laporkan ke penanggung jawab sistem.');

        return self::FAILURE;
    }
}
