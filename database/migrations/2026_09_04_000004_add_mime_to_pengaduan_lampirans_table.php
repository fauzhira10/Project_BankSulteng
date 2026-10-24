<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lampiran kini disimpan apa adanya sesuai format kiriman CS (tidak dikonversi ke PDF),
     * sehingga tipe MIME asli perlu disimpan dan kolom jumlah halaman tidak lagi relevan.
     */
    public function up(): void
    {
        Schema::table('pengaduan_lampirans', function (Blueprint $table) {
            $table->string('mime', 100)->default('application/pdf')->after('sumber');
        });

        // Baris lama (hasil konversi versi sebelumnya) seluruhnya berupa PDF
        DB::table('pengaduan_lampirans')->update(['mime' => 'application/pdf']);

        Schema::table('pengaduan_lampirans', function (Blueprint $table) {
            $table->dropColumn('jumlah_halaman');
        });
    }

    public function down(): void
    {
        Schema::table('pengaduan_lampirans', function (Blueprint $table) {
            $table->unsignedSmallInteger('jumlah_halaman')->default(1)->after('sumber');
            $table->dropColumn('mime');
        });
    }
};
