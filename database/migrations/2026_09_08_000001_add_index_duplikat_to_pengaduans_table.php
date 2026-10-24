<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index pendukung deteksi keluhan berulang di sisi pengaduan CS.
 *
 * Sengaja BUKAN unique: satu nasabah boleh melapor dengan nomor resi yang sama
 * pada tanggal berbeda, dan pengaduan yang ditolak pun tetap tersimpan.
 * Tabel jurnals tidak perlu index baru karena jurnal_unique_kombinasi sudah
 * dimulai dari (nama_nasabah, no_resi, ...) sehingga prefix kirinya terpakai.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaduans', function (Blueprint $table) {
            $table->index(['nama_nasabah', 'no_resi'], 'pengaduans_nama_resi_index');
        });
    }

    public function down(): void
    {
        Schema::table('pengaduans', function (Blueprint $table) {
            $table->dropIndex('pengaduans_nama_resi_index');
        });
    }
};
