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
        Schema::table('users', function (Blueprint $table) {
            $table->string('nama')->nullable()->after('email');
            $table->enum('role', ['admin', 'siswa'])->default('siswa')->after('nama');
            $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif')->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nama', 'role', 'status']);
        });
    }
};
