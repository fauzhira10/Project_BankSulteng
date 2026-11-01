<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi Skema Bintang (Star Schema) untuk Gudang Data / Data Warehouse Keluhan.
 * Terdiri dari 3 tabel dimensi (Waktu, Cabang, Transaksi) dan 1 tabel fakta (Fact Keluhan).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Dimensi Waktu
        Schema::create('dim_waktu', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->unsignedSmallInteger('tahun');
            $table->string('kuartal', 10); // Q1, Q2, Q3, Q4
            $table->unsignedTinyInteger('bulan'); // 1 - 12
            $table->string('nama_bulan', 20); // Januari - Desember
            $table->unsignedTinyInteger('hari'); // 1 - 31
            $table->string('nama_hari', 20); // Senin - Minggu
            $table->timestamps();

            $table->index(['tahun', 'bulan']);
            $table->index('kuartal');
        });

        // 2. Tabel Dimensi Cabang
        Schema::create('dim_cabang', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_cabang_id')->unique();
            $table->string('kode_cabang', 50)->nullable();
            $table->string('nama_cabang', 255);
            $table->timestamps();

            $table->index('kode_cabang');
        });

        // 3. Tabel Dimensi Transaksi
        Schema::create('dim_transaksi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_transaksi_id')->unique();
            $table->string('jenis_transaksi', 255);
            $table->string('channel', 100);
            $table->timestamps();

            $table->index('channel');
        });

        // 4. Tabel Fakta Keluhan (Fact Table)
        Schema::create('fact_keluhan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jurnal_id')->unique(); // ID transaksi operasional
            $table->foreignId('dim_waktu_id')->constrained('dim_waktu')->cascadeOnDelete();
            $table->foreignId('dim_cabang_id')->constrained('dim_cabang')->cascadeOnDelete();
            $table->foreignId('dim_transaksi_id')->constrained('dim_transaksi')->cascadeOnDelete();

            // Metrik & Atribut Fakta
            $table->string('status', 50)->default('Menunggu');
            $table->boolean('is_selesai')->default(false);
            $table->decimal('nominal_transaksi', 16, 2)->default(0);
            $table->decimal('biaya_admin', 12, 2)->default(0);
            $table->integer('durasi_penyelesaian_hari')->default(0);

            $table->timestamps();

            $table->index('status');
            $table->index('is_selesai');
            $table->index(['dim_waktu_id', 'dim_cabang_id']);
            $table->index(['dim_waktu_id', 'dim_transaksi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_keluhan');
        Schema::dropIfExists('dim_transaksi');
        Schema::dropIfExists('dim_cabang');
        Schema::dropIfExists('dim_waktu');
    }
};
