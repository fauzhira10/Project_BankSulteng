<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menyamakan nomor tiket jurnal dengan nomor tiket pengaduan asalnya.
     *
     * Jurnal yang dibuat SEBELUM migrasi penggantian format (000006) menyalin
     * nomor pengaduan lama berformat PGD-{kode cabang}-{tanggal}-{urut}. Migrasi
     * 000006 hanya memperbarui tabel pengaduans, sehingga jurnal yang sudah
     * tertaut tertinggal memakai format lama dan tampil berbeda dengan nomor
     * yang dilihat CS cabang.
     *
     * Aturannya: satu keluhan hanya punya satu nomor tiket, yaitu nomor milik
     * pengaduan asalnya.
     */
    public function up(): void
    {
        $baris = DB::table('jurnals')
            ->join('pengaduans', 'pengaduans.jurnal_id', '=', 'jurnals.id')
            ->where('jurnals.no_tiket', 'LIKE', 'PGD-%')
            ->select('jurnals.id as jurnal_id', 'pengaduans.nomor_tiket')
            ->get();

        foreach ($baris as $row) {
            DB::table('jurnals')
                ->where('id', $row->jurnal_id)
                ->update(['no_tiket' => $row->nomor_tiket]);
        }
    }

    /**
     * Catatan: nomor lama berformat PGD-... tidak disimpan sehingga tidak dapat
     * dipulihkan. Migrasi ini sengaja tidak melakukan apa pun saat dibatalkan.
     */
    public function down(): void
    {
        //
    }
};
