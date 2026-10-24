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
    Schema::create('audit_trails', function (Blueprint $table) {
        $table->id();
        $table->foreignId('jurnal_id')->constrained('jurnals');
        $table->string('hash_sebelumnya')->nullable();
        $table->string('hash_sekarang');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};
