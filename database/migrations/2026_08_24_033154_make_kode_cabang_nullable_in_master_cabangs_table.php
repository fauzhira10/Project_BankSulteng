<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_cabangs', function (Blueprint $table) {
            $table->string('kode_cabang')->nullable()->change();
        });

        \Illuminate\Support\Facades\DB::table('master_cabangs')
            ->where('nama_cabang', 'LIKE', '%CALL CENTER%')
            ->update(['kode_cabang' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_cabangs', function (Blueprint $table) {
            $table->string('kode_cabang')->nullable(false)->change();
        });
    }
};
