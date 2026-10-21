<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom snapshot Radius Kantor pada tabel attendances.
     *
     * Nilai ini disalin dari pengaturan Lokasi Kantor yang berlaku PADA SAAT
     * Absen Masuk dilakukan, sehingga histori lama tidak berubah ketika Admin
     * mengubah Radius Kantor di kemudian hari.
     * Data existing tidak diubah: kolom baru nullable.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('radius_kantor_saat_absen', 10, 2)->nullable()->after('jarak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('radius_kantor_saat_absen');
        });
    }
};
