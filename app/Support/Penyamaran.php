<?php

namespace App\Support;

/**
 * Penyamaran nomor pribadi untuk tampilan layar.
 *
 * Dipakai pada layar DAFTAR — satu layar di sana menampilkan puluhan nasabah
 * sekaligus, sehingga nomor rekening yang tercetak penuh ikut terbawa setiap
 * kali layar difoto, dibagikan lewat berbagi layar, atau sekadar terlihat orang
 * yang lewat. Panel rincian dan dokumen cetak tetap menampilkan nomor utuh:
 * di sana petugas memang sedang menangani satu kasus tertentu.
 *
 * Ini tindakan tampilan, bukan kendali akses. Petugas yang melihat daftar tetap
 * berwenang membuka rinciannya; yang dikurangi adalah paparan yang tidak
 * disengaja.
 */
class Penyamaran
{
    /**
     * Banyaknya digit terakhir yang tetap terbaca.
     */
    public const EKOR = 4;

    /**
     * Jumlah titik penyamar dibuat tetap, tidak mengikuti panjang aslinya,
     * supaya panjang nomor pun tidak ikut terbaca.
     */
    protected const TITIK = '••••';

    public static function nomor(?string $nilai, int $ekor = self::EKOR): string
    {
        $bersih = trim((string) $nilai);

        if ($bersih === '' || $bersih === '-') {
            return '-';
        }

        // Nomor pendek disamarkan seluruhnya: menyisakan empat digit terakhir
        // dari nomor lima digit sama saja dengan menampilkannya utuh.
        if (mb_strlen($bersih) <= $ekor + 1) {
            return self::TITIK;
        }

        return self::TITIK.mb_substr($bersih, -$ekor);
    }
}
