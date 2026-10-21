<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom user_id (identitas eksternal siswa) ke tabel users.
     * - Tipe string panjang 30 cukup untuk format "G1 - INT0030"
     * - Nullable agar admin tidak wajib memiliki User ID
     * - Unique untuk mencegah duplikasi
     * - users.id tetap primary key, relasi tetap via users.id
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Jika kolom sudah ada (misal migrasi dijalankan ulang), skip
            if (!Schema::hasColumn('users', 'user_id')) {
                $table->string('user_id', 30)->nullable()->unique()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'user_id')) {
                // Hapus unique index dulu jika ada
                try {
                    $table->dropUnique(['user_id']);
                } catch (\Throwable $e) {
                    // ignore jika index tidak ditemukan
                }
                $table->dropColumn('user_id');
            }
        });
    }
};
