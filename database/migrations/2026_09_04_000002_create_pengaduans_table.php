<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pengaduan nasabah yang dikirim oleh CS cabang ke Admin Pusat.
     * Setelah diterima pusat, pengaduan ditautkan ke baris `jurnals` melalui `jurnal_id`.
     */
    public function up(): void
    {
        Schema::create('pengaduans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pengaduan', 40)->unique();

            // Data Pelapor (CS)
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('nama_pelapor');
            $table->foreignId('master_cabang_id')->constrained('master_cabangs');
            $table->string('kategori');
            $table->string('sub_kategori')->nullable();
            $table->string('sub_kategori_2')->nullable();

            // Data Nasabah & Transaksi
            $table->string('nama_nasabah');
            $table->string('no_hp', 30);
            $table->string('no_ktp', 32);
            $table->string('no_rekening', 50);
            $table->string('no_kartu', 50)->nullable();
            $table->foreignId('master_transaksi_id')->constrained('master_transaksis');
            $table->string('no_resi', 100);
            $table->string('terminal_transaksi')->nullable();
            $table->string('channel', 50);
            $table->decimal('nominal_transaksi', 15, 2);
            $table->date('tgl_transaksi');

            // Keterangan Detail
            $table->text('kronologi');

            // Alur Status & Tindak Lanjut Pusat
            $table->string('status', 20)->default('Terkirim');
            $table->text('catatan_pusat')->nullable();
            $table->foreignId('jurnal_id')->nullable()->constrained('jurnals')->nullOnDelete();
            $table->foreignId('diterima_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diterima_at')->nullable();
            $table->timestamp('diproses_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamp('ditolak_at')->nullable();

            $table->timestamps();

            $table->index(['master_cabang_id', 'status']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaduans');
    }
};
