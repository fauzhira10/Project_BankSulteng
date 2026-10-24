<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Modul Pengaduan Nasabah (CS Cabang → Admin Pusat)
|--------------------------------------------------------------------------
*/

return [

    // Daftar prinsipal / channel transaksi yang bisa dipilih CS
    // (identik dengan daftar pada form jurnal admin).
    'channels' => [
        'ATM LOKAL',
        'ATM BERSAMA',
        'ATM LINK',
        'FINNET',
        'MOBILE BANKING',
        'SMS BANKING',
        'DEBIT',
        'EDC BANK LAIN',
        'LAKU PANDAI',
        'QRIS',
        'CCTV',
        'ATM',
    ],

    // Pilihan terminal untuk transaksi non-mesin ATM Bank Sulteng.
    'terminal_non_atm' => [
        'MOBILE BANKING',
        'ATM BANK LAIN',
        'SMS BANKING',
        'EDC',
        'EDC BANK LAIN',
    ],

    // Jenis lampiran yang dapat diunggah CS. Kunci dipakai sebagai nilai kolom `jenis`.
    'jenis_lampiran' => [
        'foto_ktp' => [
            'label' => 'Foto KTP Nasabah',
            'wajib' => true,
            'keterangan' => 'Foto KTP yang jelas & terbaca (bisa lebih dari satu foto).',
        ],
        'buku_tabungan' => [
            'label' => 'Buku Tabungan',
            'wajib' => false,
            'keterangan' => 'Halaman identitas & mutasi terkait transaksi.',
        ],
        'kartu_atm' => [
            'label' => 'Kartu ATM / Debit',
            'wajib' => false,
            'keterangan' => 'Foto bagian depan kartu (nomor kartu terlihat).',
        ],
        'form_keluhan' => [
            'label' => 'Formulir Keluhan Nasabah',
            'wajib' => false,
            'keterangan' => 'Formulir pengaduan yang telah ditandatangani nasabah.',
        ],
        'lainnya' => [
            'label' => 'Lampiran Lainnya',
            'wajib' => false,
            'keterangan' => 'Bukti pendukung lain: struk, screenshot, surat, dll.',
        ],
    ],

    // Batas unggahan. Format berkas selalu dipertahankan sesuai kiriman CS
    // (JPG tetap JPG, PNG tetap PNG, PDF tidak pernah disentuh).
    'ekstensi_diizinkan' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
    'max_ukuran_file_kb' => 5120,   // 5 MB per file
    'max_file_per_jenis' => 5,

    // Optimalisasi gambar agar penyimpanan tetap ringan.
    // Gambar diperkecil hanya bila sisi terpanjangnya melebihi batas di bawah,
    // lalu disimpan ulang dengan format yang sama. Gambar yang sudah kecil dan
    // orientasinya benar disimpan apa adanya tanpa dikodekan ulang.
    'lebar_maks_gambar' => 2000,    // piksel, sisi terpanjang
    'kualitas_gambar' => 85,        // kualitas JPEG & WEBP (0-100)
    'kompresi_png' => 6,            // level kompresi PNG (0-9)
    'batas_piksel_proses' => 40000000, // di atas ini gambar disimpan apa adanya (jaga memori)

    // Disk penyimpanan lampiran (privat, tidak dapat diakses publik)
    'disk' => 'local',
];
