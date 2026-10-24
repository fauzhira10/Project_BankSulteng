<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyimpan NIK nasabah dalam bentuk terenkripsi.
 *
 * Salinan basis data yang bocor tidak boleh langsung menjadi daftar nomor
 * identitas nasabah. Pembacaan dan penulisannya ditangani cast `encrypted` pada
 * App\Models\Pengaduan, jadi tidak ada kode lain yang perlu berubah.
 *
 * Hanya kolom ini yang aman dienkripsi. `nama_nasabah`, `no_resi`, `no_rekening`
 * dan `no_kartu` dipakai indeks unik serta deteksi keluhan berulang, sedangkan
 * `no_hp` ikut dicari di `Pengaduan::scopeCari()` — mengenkripsi salah satunya
 * akan mematikan pencarian atau anti-duplikat.
 *
 * PENTING: sejak migrasi ini dijalankan, isi kolom `no_ktp` bergantung sepenuhnya
 * pada APP_KEY. Kunci yang hilang berarti NIK seluruh pengaduan tidak dapat
 * dipulihkan — cadangkan APP_KEY terpisah dari cadangan basis data sebelum
 * menjalankannya. Lihat DOCUMENTATION.md bagian "Enkripsi NIK".
 *
 * Kolom dilebarkan lebih dulu: hasil enkripsi Laravel berupa muatan base64
 * sepanjang ratusan karakter, jauh melebihi 32 karakter kolom aslinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaduans', function (Blueprint $table) {
            $table->text('no_ktp')->change();
        });

        DB::table('pengaduans')
            ->select('id', 'no_ktp')
            ->orderBy('id')
            ->chunkById(200, function ($baris) {
                foreach ($baris as $satu) {
                    if (blank($satu->no_ktp) || $this->terenkripsi($satu->no_ktp)) {
                        continue;
                    }

                    DB::table('pengaduans')
                        ->where('id', $satu->id)
                        ->update(['no_ktp' => Crypt::encryptString($satu->no_ktp)]);
                }
            });
    }

    public function down(): void
    {
        DB::table('pengaduans')
            ->select('id', 'no_ktp')
            ->orderBy('id')
            ->chunkById(200, function ($baris) {
                foreach ($baris as $satu) {
                    if (blank($satu->no_ktp) || ! $this->terenkripsi($satu->no_ktp)) {
                        continue;
                    }

                    DB::table('pengaduans')
                        ->where('id', $satu->id)
                        ->update(['no_ktp' => Crypt::decryptString($satu->no_ktp)]);
                }
            });

        Schema::table('pengaduans', function (Blueprint $table) {
            $table->string('no_ktp', 32)->change();
        });
    }

    /**
     * Apakah nilai ini sudah berupa muatan terenkripsi.
     *
     * Dipakai agar migrasi aman dijalankan ulang: baris yang sudah terenkripsi
     * tidak ikut dienkripsi dua kali, yang akan membuatnya tidak terbaca.
     */
    private function terenkripsi(string $nilai): bool
    {
        try {
            Crypt::decryptString($nilai);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
};
