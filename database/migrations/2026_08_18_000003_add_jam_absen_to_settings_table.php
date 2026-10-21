<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan pengaturan jam buka Absen Masuk (08:00 - 08:30 WIB)
     * dan tetapkan koordinat resmi Lokasi Kantor Pacitan
     * beserta Radius Kantor 100 meter.
     * Idempotent (updateOrInsert) agar aman dijalankan berulang.
     */

    public function up(): void
    {
        $data = [
            'jam_absen_mulai'           => '08:00',
            'jam_absen_selesai'         => '08:30',
            'lokasi_kantor_latitude'    => '-8.198644745989196',
            'lokasi_kantor_longitude'   => '111.11795852827215',
            'lokasi_kantor_radius'      => '100',
        ];

        foreach ($data as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['jam_absen_mulai', 'jam_absen_selesai'])->delete();
    }
};