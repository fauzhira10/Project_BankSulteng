<?php

use App\Services\NomorTiketService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Merapikan nomor tiket lama yang terlanjur tersimpan dengan spasi, misalnya
 * "BS - 2026081347674" menjadi "BS-2026081347674".
 *
 * Nomor itu berasal dari ketikan petugas pada jurnal yang diinput langsung
 * (tanpa pengaduan CS), karena jalur tersebut memang menerima format apa pun.
 * Selain merapikan tampilan, perbaikan ini menutup dua celah nyata: pencarian
 * "LIKE %kata%" pada JurnalController::index tidak menemukan nomor berspasi,
 * dan NomorTiketService::angkaTerpakai() yang memakai LIKE 'BS-{tanggal}%' tidak
 * menghitungnya sebagai nomor terpakai sehingga berpotensi terbit kembar.
 *
 * Nomor manual berupa teks bebas seperti "PRO AKTIF" tidak tersentuh, sebab
 * NomorTiketService::rapikan() hanya mengubah nilai berbentuk "BS" + angka.
 *
 * Memakai query builder, bukan model, agar JurnalObserver tidak ikut terpicu:
 * perapian ini hanya menyangkut satu kolom dan tidak boleh mengubah status
 * pengaduan CS yang tertaut.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('jurnals')
            ->where('no_tiket', 'LIKE', 'BS%')
            ->where('no_tiket', 'LIKE', '% %')
            ->orderBy('id')
            ->each(function ($baris) {
                $rapi = NomorTiketService::rapikan($baris->no_tiket);

                if ($rapi !== $baris->no_tiket) {
                    DB::table('jurnals')->where('id', $baris->id)->update(['no_tiket' => $rapi]);
                }
            });
    }

    public function down(): void
    {
        // Sengaja dikosongkan. Spasi itu salah ketik, jadi mengembalikannya tidak
        // ada gunanya, dan bentuk aslinya pun tidak dapat dipulihkan dari nomor
        // yang sudah rapi.
    }
};
