<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom peran (role), cabang penempatan, dan status aktif pada tabel users.
     * Default role 'admin' agar akun admin yang sudah ada otomatis menjadi Admin Pusat.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('username')->index();
            $table->foreignId('master_cabang_id')
                ->nullable()
                ->after('role')
                ->constrained('master_cabangs')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('master_cabang_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropConstrainedForeignId('master_cabang_id');
            } else {
                $table->dropColumn('master_cabang_id');
            }
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
