<?php

namespace App\Services;

use App\Models\Jurnal;
use App\Models\Pengaduan;
use Carbon\Carbon;
use RuntimeException;

/**
 * Pembuat nomor tiket keluhan Bank Sulteng: BS-{YYYYMMDD}{5 angka acak}.
 * Contoh: BS-2026090412345
 *
 * Nomor lahir saat CS cabang mengirim pengaduan, lalu dibawa terus ke Jurnal
 * Keluhan sehingga satu keluhan hanya punya satu nomor dari awal sampai selesai.
 * Jurnal yang diinput langsung oleh Admin Pusat (tanpa pengaduan) memperoleh
 * nomor baru dari layanan ini juga.
 *
 * Angka acak diambil dengan random_int (pembangkit acak kriptografis), lalu
 * dipastikan belum terpakai pada tanggal yang sama — dicek ke tabel pengaduans
 * maupun jurnals agar dua berkas berbeda tidak pernah memakai nomor yang sama.
 * Karena tanggal ikut menjadi bagian nomor, tersedia 100.000 nomor per tanggal.
 */
class NomorTiketService
{
    /** Awalan tetap nomor tiket */
    public const PREFIX = 'BS-';

    /** Banyak digit angka acak */
    public const DIGIT = 5;

    /** Batas percobaan angka acak sebelum beralih ke pencarian berurutan */
    protected const MAKS_PERCOBAAN = 25;

    /**
     * Buat nomor tiket baru yang dijamin belum terpakai.
     *
     * Panggil di dalam DB::transaction agar penguncian baris efektif dan dua
     * petugas yang menyimpan bersamaan tidak memperoleh nomor kembar.
     */
    public static function buat(mixed $tanggal = null): string
    {
        $hari = static::tanggal($tanggal);
        $awalan = static::PREFIX.$hari->format('Ymd');
        $maks = (10 ** static::DIGIT) - 1;

        for ($i = 0; $i < static::MAKS_PERCOBAAN; $i++) {
            $kandidat = $awalan.static::pad(random_int(0, $maks));

            if (! static::terpakai($kandidat)) {
                return $kandidat;
            }
        }

        // Cadangan: seluruh percobaan acak bentrok (praktis mustahil).
        // Cari angka pertama yang masih bebas agar penyimpanan tidak pernah gagal.
        $terpakai = static::angkaTerpakai($awalan);

        for ($n = 0; $n <= $maks; $n++) {
            if (! isset($terpakai[$n])) {
                return $awalan.static::pad($n);
            }
        }

        throw new RuntimeException("Kuota nomor tiket untuk tanggal {$hari->format('d/m/Y')} sudah habis.");
    }

    /**
     * Apakah nomor sudah dipakai pengaduan cabang atau jurnal keluhan.
     *
     * lockForUpdate menahan celah indeks sehingga transaksi lain tidak dapat
     * menyisipkan nomor yang sama saat pengecekan sedang berlangsung.
     */
    public static function terpakai(string $nomor): bool
    {
        return Pengaduan::query()->where('nomor_tiket', $nomor)->lockForUpdate()->exists()
            || Jurnal::query()->where('no_tiket', $nomor)->lockForUpdate()->exists();
    }

    /**
     * Rapikan nomor tiket yang diketik petugas.
     *
     *   "BS - 2026081347674"  →  "BS-2026081347674"
     *   "bs 2026081347674"    →  "BS-2026081347674"
     *
     * Hanya berlaku untuk nilai yang memang sudah berbentuk nomor BS, yaitu
     * awalan "BS" diikuti angka. Nomor manual berupa teks bebas seperti
     * "PRO AKTIF" dibiarkan utuh, sebab jurnal yang diinput langsung Admin Pusat
     * nomornya diketik dari berkas kertas dan format apa pun memang diterima.
     */
    public static function rapikan(?string $nomor): string
    {
        $bersih = trim((string) $nomor);

        // Awalan huruf, tanda hubung boleh tidak diketik, spasi di mana pun dibuang.
        $awalan = rtrim(static::PREFIX, '-');
        $pola = '/^'.preg_quote($awalan, '/').'\s*-?\s*([\d\s]+)$/i';

        if (preg_match($pola, $bersih, $cocok)) {
            return static::PREFIX.preg_replace('/\s+/', '', $cocok[1]);
        }

        return $bersih;
    }

    /**
     * Apakah pola nomor sesuai format resmi BS-{YYYYMMDD}{5 angka}.
     */
    public static function formatValid(?string $nomor): bool
    {
        $pola = '/^'.preg_quote(static::PREFIX, '/').'\d{8}\d{'.static::DIGIT.'}$/';

        return (bool) preg_match($pola, trim((string) $nomor));
    }

    /**
     * Angka yang sudah terpakai pada satu awalan tanggal, sebagai kunci array.
     *
     * @return array<int, bool>
     */
    protected static function angkaTerpakai(string $awalan): array
    {
        $nomor = Pengaduan::query()->where('nomor_tiket', 'LIKE', $awalan.'%')->pluck('nomor_tiket')
            ->merge(Jurnal::query()->where('no_tiket', 'LIKE', $awalan.'%')->pluck('no_tiket'));

        $hasil = [];

        foreach ($nomor as $item) {
            $angka = substr((string) $item, strlen($awalan));

            if (ctype_digit($angka) && strlen($angka) === static::DIGIT) {
                $hasil[(int) $angka] = true;
            }
        }

        return $hasil;
    }

    protected static function tanggal(mixed $tanggal): Carbon
    {
        if ($tanggal instanceof Carbon) {
            return $tanggal;
        }

        $teks = trim((string) $tanggal);

        if ($teks === '') {
            return Carbon::now();
        }

        try {
            return Carbon::parse($teks);
        } catch (\Throwable) {
            return Carbon::now();
        }
    }

    protected static function pad(int $angka): string
    {
        return str_pad((string) $angka, static::DIGIT, '0', STR_PAD_LEFT);
    }
}
