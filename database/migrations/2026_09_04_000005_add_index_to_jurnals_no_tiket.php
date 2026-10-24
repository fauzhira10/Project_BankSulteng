<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indeks (bukan unik) pada no_tiket.
     *
     * Dipakai saat pembuatan nomor tiket otomatis untuk mengecek apakah sebuah
     * nomor sudah terpakai, dan agar penguncian baris pada pengecekan tersebut
     * mengunci celah indeks yang sempit, bukan seluruh tabel.
     *
     * Sengaja TIDAK unik: data historis hasil impor Excel mengandung nomor tiket
     * kembar (mis. "PRO AKTIF" dan beberapa nomor BS-... yang dipakai berulang),
     * sehingga batasan unik akan menolak data yang sudah ada.
     */
    public function up(): void
    {
        Schema::table('jurnals', function (Blueprint $table) {
            $table->index('no_tiket', 'jurnals_no_tiket_index');
        });
    }

    public function down(): void
    {
        Schema::table('jurnals', function (Blueprint $table) {
            $table->dropIndex('jurnals_no_tiket_index');
        });
    }
};
