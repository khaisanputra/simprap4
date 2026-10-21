<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan relasi laporan_id ke laporan_sales.
     * Nullable agar data lama tetap aman, dan foreign key aman untuk data existing.
     */
    public function up(): void
    {
        Schema::table('laporan_sales', function (Blueprint $table) {
            // Tambah kolom laporan_id nullable setelah user_id, dengan FK ke laporans
            // Gunakan foreignId nullable + constrained + nullOnDelete agar data lama aman
            // Dan jika laporan utama dihapus, set null (atau cascade) — pilih nullOnDelete untuk keamanan
            $table->foreignId('laporan_id')
                ->nullable()
                ->after('user_id')
                ->constrained('laporans')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->index('laporan_id');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_sales', function (Blueprint $table) {
            // Drop FK dulu jika ada, lalu kolom
            try {
                $table->dropForeign(['laporan_id']);
            } catch (\Throwable $e) {
                // ignore jika foreign tidak ada (misal SQLite)
            }
            try {
                $table->dropIndex(['laporan_id']);
            } catch (\Throwable $e) {
            }
            if (Schema::hasColumn('laporan_sales', 'laporan_id')) {
                $table->dropColumn('laporan_id');
            }
        });
    }
};
