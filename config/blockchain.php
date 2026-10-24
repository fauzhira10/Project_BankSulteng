<?php

/*
|--------------------------------------------------------------------------
| Blockchain Jejak Audit
|--------------------------------------------------------------------------
|
| Jejak audit (tabel audit_trails) dikumpulkan menjadi blok-blok berantai di
| tabel blok_audits: setiap blok memuat merkle root dari catatan-catatannya,
| menyegel hash blok sebelumnya, dan harus "ditambang" (proof of work) sebelum
| sah. Hash tiap blok lalu dijangkarkan ke kontrak JangkarAudit di Ethereum,
| sehingga pemalsuan yang menambang ulang seluruh rantai di server pun tetap
| ketahuan. Lihat DOCUMENTATION.md bagian 8.11.
|
*/

return [

    // Jumlah maksimum catatan audit dalam satu blok.
    'ukuran_blok' => (int) env('BLOCKCHAIN_UKURAN_BLOK', 10),

    // Jumlah angka nol heksadesimal di awal hash blok yang wajib dipenuhi.
    // Tiap kenaikan satu tingkat membuat penambangan rata-rata 16x lebih lama:
    // 4 ≈ 65 ribu percobaan (sekejap), 6 ≈ 16 juta percobaan (puluhan detik di PHP).
    'tingkat_kesulitan' => max(1, min(8, (int) env('BLOCKCHAIN_KESULITAN', 4))),

    'ethereum' => [
        // Matikan bila server tidak punya jalur keluar ke internet. Blockchain
        // lokal tetap berjalan; hanya penjangkaran ke Ethereum yang dilewati.
        'aktif' => (bool) env('ETH_JANGKAR_AKTIF', false),

        'rpc_url' => env('ETH_RPC_URL'),
        'alamat_kontrak' => env('ETH_ALAMAT_KONTRAK'),
        'blok_deploy' => env('ETH_BLOK_DEPLOY'),

        // Hanya untuk tautan di Block Explorer.
        'penjelajah' => rtrim((string) env('ETH_PENJELAJAH', 'https://sepolia.etherscan.io'), '/'),

        // Kunci privat dompet (ETH_KUNCI_PRIVAT) sengaja TIDAK dibaca di sini:
        // skrip blockchain/jangkar.mjs mengambilnya langsung dari .env, sehingga
        // kunci itu tidak ikut tersimpan di bootstrap/cache/config.php.

        'node_bin' => env('NODE_BIN', 'node'),
        'batas_waktu' => (int) env('ETH_BATAS_WAKTU', 120),
    ],

];
