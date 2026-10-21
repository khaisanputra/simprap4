<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arsip snapshot statistik bulanan per siswa.
     * - Tidak menghapus/mengganti data attendances asli
     * - Satu siswa hanya boleh punya 1 arsip per bulan+ tahun (unique)
     * - bulan disimpan 1-12
     */
    public function up(): void
    {
        Schema::create('monthly_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->unsignedTinyInteger('bulan'); // 1-12
            $table->unsignedSmallInteger('tahun'); // e.g. 2026
            $table->unsignedInteger('jumlah_masuk')->default(0);
            $table->unsignedInteger('jumlah_pulang')->default(0);
            $table->unsignedInteger('jumlah_izin')->default(0);
            $table->unsignedInteger('jumlah_sakit')->default(0);
            $table->unsignedInteger('jumlah_dispensasi')->default(0);
            $table->unsignedInteger('jumlah_telat')->default(0);
            $table->unsignedInteger('jumlah_tidak_masuk')->default(0);
            $table->unsignedTinyInteger('persentase_kehadiran')->default(0); // 0-100
            $table->timestamps();

            $table->unique(['user_id', 'bulan', 'tahun'], 'uniq_user_bulan_tahun');
            $table->index(['tahun', 'bulan']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_statistics');
    }
};
