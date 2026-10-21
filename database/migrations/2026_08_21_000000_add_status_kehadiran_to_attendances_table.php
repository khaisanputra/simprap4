<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom status_kehadiran & keterangan pada tabel attendances.
     *
     * - status_kehadiran: Masuk | Izin | Dispensasi | Sakit (default Masuk)
     * - keterangan: alasan/detail untuk Izin/Dispensasi/Sakit, nullable.
     *
     * Data existing tetap utuh: baris lama akan memiliki status_kehadiran = 'Masuk'
     * (fallback) agar histori tidak berubah. Kolom baru nullable/default, sehingga
     * tidak merusak data Absen Masuk/Pulang yang sudah ada. GPS/lokasi tetap
     * nullable untuk mengakomodasi status tanpa GPS.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('status_kehadiran', 20)->default('Masuk')->after('status')->index();
            $table->text('keterangan')->nullable()->after('status_kehadiran');
        });

        // Backfill: baris lama yang status_kehadirannya masih default/null
        // disesuaikan agar histori tetap konsisten (status Pulang -> Masuk).
        // Jika status lama sudah Pulang, status_kehadiran tetap Masuk karena
        // mereka memang hadir secara fisik sebelum pulang.
        \Illuminate\Support\Facades\DB::table('attendances')
            ->whereNull('status_kehadiran')
            ->orWhere('status_kehadiran', '')
            ->update(['status_kehadiran' => 'Masuk']);

        // Pastikan baris dengan status Pulang juga memiliki status_kehadiran Masuk
        // (jika migration default belum mencakup karena query di atas tidak match).
        // Ini idempoten dan aman untuk data existing.
        \Illuminate\Support\Facades\DB::table('attendances')
            ->whereIn('status', ['Masuk', 'Pulang', 'Belum Pulang'])
            ->where(function ($q) {
                $q->whereNull('status_kehadiran')
                  ->orWhere('status_kehadiran', 'Masuk');
            })
            ->update(['status_kehadiran' => 'Masuk']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['status_kehadiran']);
            $table->dropColumn(['status_kehadiran', 'keterangan']);
        });
    }
};
