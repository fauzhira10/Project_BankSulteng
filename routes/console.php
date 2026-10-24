<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tugas Terjadwal
|--------------------------------------------------------------------------
|
| Rantai hash jejak audit hanya berguna bila benar-benar diperiksa. Tanpa
| pemeriksaan berkala, penyuntingan langsung ke tabel audit_trails lewat
| phpMyAdmin atau klien SQL lain baru ketahuan saat ada yang curiga —
| yang bisa berbulan-bulan setelah kejadiannya.
|
| Keluarannya ditulis ke storage/logs/audit-periksa.log, bukan dikirim lewat
| surel, karena server ini belum tentu punya MAIL_MAILER yang berfungsi.
| Perintahnya keluar dengan kode gagal bila rantainya putus.
|
| Penjadwal Laravel baru berjalan bila ada satu tugas sistem yang memanggil
| `php artisan schedule:run` setiap menit — lihat DOCUMENTATION.md bagian 8.4.
|
*/

Schedule::command('audit:periksa')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/audit-periksa.log'));

/*
| Blockchain jejak audit (DOCUMENTATION.md bagian 8.11). Catatan audit baru
| ditambang menjadi blok tiap sepuluh menit dan, bila ETH_JANGKAR_AKTIF=true,
| langsung dijangkarkan ke Ethereum. Seluruh rantai blok diperiksa tiap malam,
| termasuk pencocokan dengan Ethereum.
*/

Schedule::command('blockchain:tambang')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/blockchain.log'));

Schedule::command('blockchain:periksa --jangkar')
    ->dailyAt('01:10')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/blockchain-periksa.log'));
