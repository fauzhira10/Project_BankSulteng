// SPDX-License-Identifier: MIT
pragma solidity ^0.8.26;

/**
 * JangkarAudit — buku notaris publik untuk blok jejak audit Jurnal Keluhan Bank Sulteng.
 *
 * Aplikasi mengumpulkan jejak audit menjadi blok-blok berantai di basis datanya
 * sendiri. Kontrak ini menyimpan HANYA hash tiap blok beserta merkle root-nya,
 * tidak pernah data nasabah. Karena data di Ethereum tidak dapat diubah siapa
 * pun, termasuk pemilik kontrak, hash yang tercatat di sini menjadi pembanding
 * yang tidak dapat dipalsukan: bila isi blok di server kelak berbeda dengan yang
 * tercatat di sini, server itulah yang telah diubah.
 *
 * Aturan yang dijaga kontrak:
 *  - Hanya alamat yang men-deploy (pemilik) yang boleh menulis.
 *  - Satu nomor blok hanya dapat dijangkarkan SEKALI. Tidak ada fungsi ubah atau
 *    hapus, sehingga pemilik pun tidak dapat menimpa jangkar lama dengan hash
 *    blok hasil pemalsuan.
 */
contract JangkarAudit {
    struct Jangkar {
        bytes32 hashBlok;
        bytes32 merkleRoot;
        uint64 waktu;
    }

    address public immutable pemilik;
    uint256 public jumlahBlok;

    mapping(uint256 => Jangkar) private daftarJangkar;

    event BlokDijangkarkan(uint256 indexed nomorBlok, bytes32 hashBlok, bytes32 merkleRoot, uint256 waktu);

    error BukanPemilik();
    error HashKosong();
    error SudahDijangkarkan(uint256 nomorBlok);

    constructor() {
        pemilik = msg.sender;
    }

    function jangkarkan(uint256 nomorBlok, bytes32 hashBlok, bytes32 merkleRoot) external {
        if (msg.sender != pemilik) revert BukanPemilik();
        if (hashBlok == bytes32(0)) revert HashKosong();
        if (daftarJangkar[nomorBlok].hashBlok != bytes32(0)) revert SudahDijangkarkan(nomorBlok);

        daftarJangkar[nomorBlok] = Jangkar(hashBlok, merkleRoot, uint64(block.timestamp));
        jumlahBlok++;

        emit BlokDijangkarkan(nomorBlok, hashBlok, merkleRoot, block.timestamp);
    }

    /**
     * Hash blok yang tercatat untuk nomor tertentu. hashBlok bernilai nol bila
     * blok itu belum pernah dijangkarkan.
     */
    function ambil(uint256 nomorBlok) external view returns (bytes32 hashBlok, bytes32 merkleRoot, uint256 waktu) {
        Jangkar storage j = daftarJangkar[nomorBlok];

        return (j.hashBlok, j.merkleRoot, j.waktu);
    }
}
