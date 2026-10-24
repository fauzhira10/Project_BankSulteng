<?php

namespace App\Support;

/**
 * Pohon Merkle biner dengan SHA-256, gaya Bitcoin.
 *
 * Daun adalah hash heksadesimal (64 karakter). Induk = sha256(kiri . kanan) atas
 * teks heksadesimalnya. Bila satu tingkat berjumlah ganjil, daun terakhir
 * dipasangkan dengan dirinya sendiri. Akar pohon tanpa daun = sha256('').
 *
 * Satu perubahan kecil pada daun mana pun mengubah akarnya, sehingga satu hash
 * (merkle root) cukup untuk menyegel seluruh isi blok.
 */
class MerkleTree
{
    /**
     * Seluruh tingkat pohon, dari daun (indeks 0) sampai akar (indeks terakhir).
     *
     * @param  list<string>  $daun
     * @return list<list<string>>
     */
    public static function tingkat(array $daun): array
    {
        $daun = array_values($daun);

        if ($daun === []) {
            return [[hash('sha256', '')]];
        }

        $semua = [$daun];

        while (count($daun) > 1) {
            $atas = [];

            for ($i = 0; $i < count($daun); $i += 2) {
                $atas[] = self::induk($daun[$i], $daun[$i + 1] ?? $daun[$i]);
            }

            $semua[] = $atas;
            $daun = $atas;
        }

        return $semua;
    }

    /**
     * @param  list<string>  $daun
     */
    public static function akar(array $daun): string
    {
        $tingkat = self::tingkat($daun);

        return end($tingkat)[0];
    }

    /**
     * Bukti keanggotaan (merkle proof) untuk daun ke-$indeks: daftar hash saudara
     * dari bawah ke atas beserta letaknya. Dengan bukti ini, siapa pun dapat
     * menunjukkan satu catatan termasuk dalam blok tanpa perlu melihat catatan
     * lainnya.
     *
     * @param  list<string>  $daun
     * @return list<array{hash:string, posisi:'kiri'|'kanan'}>
     */
    public static function bukti(array $daun, int $indeks): array
    {
        $bukti = [];
        $tingkat = self::tingkat($daun);

        // Tingkat terakhir adalah akar, tidak punya saudara.
        foreach (array_slice($tingkat, 0, -1) as $baris) {
            $pasangan = $indeks ^ 1;

            $bukti[] = [
                'hash' => $baris[$pasangan] ?? $baris[$indeks],
                'posisi' => $indeks % 2 === 0 ? 'kanan' : 'kiri',
            ];

            $indeks = intdiv($indeks, 2);
        }

        return $bukti;
    }

    /**
     * Hitung akar dari satu daun dan buktinya.
     *
     * @param  list<array{hash:string, posisi:string}>  $bukti
     */
    public static function akarDariBukti(string $daun, array $bukti): string
    {
        $hash = $daun;

        foreach ($bukti as $langkah) {
            $hash = $langkah['posisi'] === 'kiri'
                ? self::induk($langkah['hash'], $hash)
                : self::induk($hash, $langkah['hash']);
        }

        return $hash;
    }

    public static function verifikasi(string $daun, array $bukti, string $akar): bool
    {
        return hash_equals($akar, self::akarDariBukti($daun, $bukti));
    }

    protected static function induk(string $kiri, string $kanan): string
    {
        return hash('sha256', $kiri.$kanan);
    }
}
