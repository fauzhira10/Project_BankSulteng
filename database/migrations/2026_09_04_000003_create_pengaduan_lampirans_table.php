<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lampiran pengaduan (KTP, buku tabungan, kartu ATM, form keluhan, lainnya).
     * Semua lampiran disimpan sebagai PDF di disk privat (storage/app/private).
     */
    public function up(): void
    {
        Schema::create('pengaduan_lampirans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengaduan_id')->constrained('pengaduans')->cascadeOnDelete();
            $table->string('jenis', 30);
            $table->string('nama_asli');
            $table->string('path');
            $table->string('sumber', 10)->default('gambar'); // gambar (hasil konversi) | pdf (upload langsung)
            $table->unsignedSmallInteger('jumlah_halaman')->default(1);
            $table->unsignedInteger('ukuran')->default(0); // byte
            $table->timestamps();

            $table->index(['pengaduan_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaduan_lampirans');
    }
};
