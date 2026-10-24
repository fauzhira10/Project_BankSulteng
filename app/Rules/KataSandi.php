<?php

namespace App\Rules;

/**
 * Kebijakan kata sandi tunggal untuk seluruh aplikasi.
 *
 * Sebelumnya aturannya terpecah: form Manajemen Pengguna menerima 6 karakter,
 * sementara perintah `admin:buat` menuntut 12. Akun Admin Pusat yang dibuat
 * lewat web karena itu bisa jauh lebih lemah daripada yang dibuat di server,
 * padahal keduanya membuka pintu yang sama.
 *
 * Sengaja TIDAK memakai Illuminate\Validation\Rules\Password::uncompromised():
 * aturan itu memanggil API HaveIBeenPwned lewat internet, sedangkan server bank
 * ini tidak dijamin punya jalur keluar — bila panggilannya gagal, pembuatan akun
 * ikut gagal. Panjang minimum yang lega dipilih sebagai gantinya.
 */
class KataSandi
{
    /**
     * Panjang minimum. 12 karakter dipilih agar frasa yang mudah diingat
     * ("kunciatm2026") lolos, sementara kata sandi pendek yang mudah ditebak
     * tidak.
     */
    public const PANJANG_MIN = 12;

    /**
     * Wajib memuat sedikitnya satu huruf dan satu angka. Ditulis sebagai satu
     * pola, bukan dua aturan regex terpisah, supaya pesan galatnya tunggal dan
     * tetap berbahasa Indonesia.
     */
    public const POLA = '/^(?=.*\p{L})(?=.*\d).+$/u';

    /**
     * @param  bool  $wajib  false untuk form ubah data, yang boleh membiarkan kata sandi lama.
     * @return array<int, string>
     */
    public static function aturan(bool $wajib = true): array
    {
        return [
            $wajib ? 'required' : 'nullable',
            'string',
            'min:'.self::PANJANG_MIN,
            'regex:'.self::POLA,
            'confirmed',
        ];
    }

    /**
     * Pesan galat berbahasa Indonesia untuk aturan di atas.
     *
     * @param  string  $atribut  Nama field pada request.
     * @return array<string, string>
     */
    public static function pesan(string $atribut = 'password'): array
    {
        return [
            $atribut.'.required' => 'Kata sandi wajib diisi.',
            $atribut.'.min' => 'Kata sandi minimal :min karakter.',
            $atribut.'.regex' => 'Kata sandi harus memuat sedikitnya satu huruf dan satu angka.',
            $atribut.'.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ];
    }

    /**
     * Keterangan singkat untuk ditampilkan di form dan prompt terminal.
     */
    public static function keterangan(): string
    {
        return 'Minimal '.self::PANJANG_MIN.' karakter, memuat huruf dan angka';
    }
}
