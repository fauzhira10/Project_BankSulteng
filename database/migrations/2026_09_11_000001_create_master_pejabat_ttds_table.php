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
        Schema::create('master_pejabat_ttds', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('slot')->comment('1: Staf, 2: Pemimpin Unit, 3: PINBAG, 4: Pemimpin Divisi');
            $table->string('nama');
            $table->string('jabatan');
            $table->string('nip')->nullable();
            $table->longText('ttd_image')->nullable()->comment('Base64 Data URI or Image Path');
            $table->boolean('is_aktif')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('urutan')->default(0);
            $table->timestamps();

            $table->index(['slot', 'is_aktif']);
        });

        Schema::table('jurnals', function (Blueprint $table) {
            $table->longText('ttd_data')->nullable()->after('keterangan_log')->comment('Snapshot TTD JSON per tiket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnals', function (Blueprint $table) {
            $table->dropColumn('ttd_data');
        });

        Schema::dropIfExists('master_pejabat_ttds');
    }
};
