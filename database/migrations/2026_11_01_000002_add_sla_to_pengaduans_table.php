<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom Prioritas, Durasi SLA, dan Batas Tanggal Jatuh Tempo pada pengaduan.
     */
    public function up(): void
    {
        Schema::table('pengaduans', function (Blueprint $table) {
            $table->string('prioritas', 20)->default('Sedang')->after('kategori'); // Tinggi, Sedang, Rendah
            $table->unsignedSmallInteger('sla_hari')->default(5)->after('prioritas'); // 2, 5, 14
            $table->date('tgl_jatuh_tempo')->nullable()->after('sla_hari'); // Tanggal deadline penyelesaian
            
            $table->index('prioritas');
            $table->index('tgl_jatuh_tempo');
        });
    }

    public function down(): void
    {
        Schema::table('pengaduans', function (Blueprint $table) {
            $table->dropIndex(['prioritas']);
            $table->dropIndex(['tgl_jatuh_tempo']);
            $table->dropColumn(['prioritas', 'sla_hari', 'tgl_jatuh_tempo']);
        });
    }
};
