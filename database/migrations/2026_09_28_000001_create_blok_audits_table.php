<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blok-blok blockchain jejak audit.
 *
 * Blok bernomor N memuat seluruh baris audit_trails dengan id di rentang
 * (audit_id_akhir blok N-1, audit_id_akhir blok N]. Rentangnya ditetapkan oleh
 * batas atas saja, bukan daftar id, supaya baris yang kelak disisipkan ke tengah
 * rentang tetap ikut dihitung saat pemeriksaan dan langsung ketahuan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blok_audits', function (Blueprint $table) {
            $table->id();

            // Tinggi blok. Genesis = 0.
            $table->unsignedBigInteger('nomor')->unique();

            // Unik: dua blok tidak boleh menunjuk induk yang sama (tidak ada
            // percabangan / fork). Genesis menunjuk 64 angka nol.
            $table->char('hash_sebelumnya', 64)->unique();

            $table->char('merkle_root', 64);
            $table->unsignedBigInteger('audit_id_awal')->nullable();
            $table->unsignedBigInteger('audit_id_akhir');
            $table->unsignedInteger('jumlah_transaksi');

            // Salinan [id, hash] tiap catatan saat ditambang. Ikut tersegel lewat
            // merkle_root, dan dipakai untuk menunjuk catatan mana yang berubah.
            // Sengaja longText, bukan json: MySQL mengubah bentuk kolom json.
            $table->longText('daftar_transaksi');

            // Proof of work
            $table->unsignedTinyInteger('tingkat_kesulitan');
            $table->unsignedBigInteger('nonce');
            $table->char('hash_blok', 64)->unique();
            $table->timestamp('ditambang_pada');
            $table->unsignedInteger('durasi_tambang_ms')->default(0);

            // Penjangkaran ke Ethereum. Kolom-kolom ini TIDAK ikut dihash karena
            // baru terisi setelah blok ditambang.
            $table->string('jangkar_status', 20)->nullable(); // null | terkirim | gagal | konflik
            $table->string('jangkar_jaringan', 40)->nullable();
            $table->string('jangkar_tx', 66)->nullable();
            $table->unsignedBigInteger('jangkar_blok_eth')->nullable();
            $table->timestamp('jangkar_pada')->nullable();
            $table->text('jangkar_galat')->nullable();

            $table->timestamps();

            $table->index('jangkar_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blok_audits');
    }
};
