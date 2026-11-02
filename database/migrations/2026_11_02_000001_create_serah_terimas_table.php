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
        Schema::create('serah_terimas', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_dokumen', 50)->unique();
            $table->date('tanggal_serah_terima');
            $table->unsignedInteger('total_tiket')->default(0);
            $table->decimal('total_nominal', 15, 2)->default(0);
            $table->json('tiket_ids')->nullable();
            $table->string('blockchain_hash', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tanggal_serah_terima');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serah_terimas');
    }
};
