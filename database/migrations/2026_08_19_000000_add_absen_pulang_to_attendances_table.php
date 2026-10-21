<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom Absen Pulang (foto, GPS, jarak, radius snapshot)
     * pada tabel attendances.
     *
     * Data Absen Masuk tidak diubah: kolom baru nullable, kolom lama
     * (foto, latitude, longitude, accuracy, jarak) tetap dipakai untuk
     * Absen Masuk. Data existing tidak terhapus.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('foto_pulang')->nullable()->after('jarak');
            $table->double('latitude_pulang')->nullable()->after('foto_pulang');
            $table->double('longitude_pulang')->nullable()->after('latitude_pulang');
            $table->double('accuracy_pulang')->nullable()->after('longitude_pulang');
            $table->decimal('jarak_pulang', 10, 2)->nullable()->after('accuracy_pulang');
            $table->decimal('radius_kantor_saat_pulang', 10, 2)->nullable()->after('jarak_pulang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'foto_pulang',
                'latitude_pulang',
                'longitude_pulang',
                'accuracy_pulang',
                'jarak_pulang',
                'radius_kantor_saat_pulang',
            ]);
        });
    }
};
