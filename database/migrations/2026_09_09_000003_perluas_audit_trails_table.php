<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membentuk ulang tabel audit_trails menjadi jejak audit yang benar-benar dipakai.
 *
 * Bentuk lamanya hanya (jurnal_id, hash_sebelumnya, hash_sekarang) dengan
 * `jurnal_id` wajib terisi, sehingga tidak dapat mencatat kejadian di luar jurnal
 * (pembukaan lampiran KTP, login gagal, penghapusan massal) dan tidak menyimpan
 * siapa pelakunya. Tabel itu tidak pernah ditulis sebaris pun — model AuditTrail
 * masih kerangka kosong — jadi dibuat ulang, bukan dialter bertahap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('audit_trails');

        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();

            // Pelaku. Disimpan sebagai relasi sekaligus salinan teks, agar jejak
            // tetap terbaca bila akunnya kelak dinonaktifkan atau dihapus.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('username')->nullable();

            $table->string('aksi', 60);

            // Objek yang terdampak (jurnal, pengaduan, lampiran, ...)
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();

            // Relasi khusus ke jurnal dipertahankan untuk penelusuran cepat,
            // tetapi nullable + nullOnDelete: jejak audit harus tetap hidup
            // setelah jurnalnya dihapus, sebab justru itu yang perlu diaudit.
            $table->foreignId('jurnal_id')->nullable()->constrained('jurnals')->nullOnDelete();

            $table->json('nilai_lama')->nullable();
            $table->json('nilai_baru')->nullable();
            $table->text('keterangan')->nullable();

            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();

            // Rantai hash: hash_sekarang = sha256(hash_sebelumnya | sidik jari baris).
            // Mengubah atau menghapus satu baris memutus rantai pada baris sesudahnya.
            $table->string('hash_sebelumnya', 64)->nullable();
            $table->string('hash_sekarang', 64);

            $table->timestamps();

            $table->index('aksi');
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_trails');

        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurnal_id')->constrained('jurnals');
            $table->string('hash_sebelumnya')->nullable();
            $table->string('hash_sekarang');
            $table->timestamps();
        });
    }
};
