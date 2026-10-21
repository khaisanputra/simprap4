<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah field baru untuk rekap sederhana:
     * - jumlah_hadir : Masuk/Telat + pulang
     * - jumlah_alpha : Alpha (tidak hadir setelah jam pulang)
     * - jumlah_laporan : hitung laporans per bulan
     * Field lama tetap dipertahankan untuk kompatibilitas arsip lama,
     * namun tidak lagi digunakan untuk tampilan Rekap Bulanan.
     */
    public function up(): void
    {
        Schema::table('monthly_statistics', function (Blueprint $table) {
            if (!Schema::hasColumn('monthly_statistics', 'jumlah_hadir')) {
                $table->unsignedInteger('jumlah_hadir')->default(0)->after('jumlah_tidak_masuk');
            }
            if (!Schema::hasColumn('monthly_statistics', 'jumlah_alpha')) {
                $table->unsignedInteger('jumlah_alpha')->default(0)->after('jumlah_hadir');
            }
            if (!Schema::hasColumn('monthly_statistics', 'jumlah_laporan')) {
                $table->unsignedInteger('jumlah_laporan')->default(0)->after('jumlah_alpha');
            }
        });
    }

    public function down(): void
    {
        Schema::table('monthly_statistics', function (Blueprint $table) {
            if (Schema::hasColumn('monthly_statistics', 'jumlah_laporan')) {
                $table->dropColumn('jumlah_laporan');
            }
            if (Schema::hasColumn('monthly_statistics', 'jumlah_alpha')) {
                $table->dropColumn('jumlah_alpha');
            }
            if (Schema::hasColumn('monthly_statistics', 'jumlah_hadir')) {
                $table->dropColumn('jumlah_hadir');
            }
        });
    }
};