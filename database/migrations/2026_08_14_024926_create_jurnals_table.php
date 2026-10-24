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
    Schema::create('jurnals', function (Blueprint $table) {
        $table->id();
        $table->string('nama_nasabah');
        $table->date('tgl_terima');
        $table->date('tgl_selesai')->nullable();
        $table->date('tgl_transaksi');
        $table->string('no_tiket')->nullable();
        $table->string('no_kartu')->nullable();
        $table->string('no_rekening');
        $table->string('no_resi');
        $table->foreignId('master_transaksi_id')->constrained('master_transaksis');
        $table->foreignId('master_cabang_id')->constrained('master_cabangs');
        $table->string('terminal_transaksi')->nullable();
        $table->decimal('nominal_transaksi', 15, 2);
        $table->text('keterangan_log')->nullable();
        $table->string('status')->default('Menunggu'); 
        $table->timestamps();

        // Ini logika unik agar 1 Nasabah dengan No Resi dan Tanggal yang sama tidak bisa diinput dua kali
        $table->unique(['nama_nasabah', 'no_resi', 'tgl_transaksi'], 'jurnal_unique_kombinasi');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnals');
    }
};
