<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perbarui aturan waktu absensi:
     * - jam_absen_mulai: 07:00 -> 08:00 WIB
     * - Pastikan jam_pulang_mulai = 17:00 (fixed)
     * Idempotent agar aman dijalankan berulang.
     */
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'jam_absen_mulai'],
            ['value' => '08:00', 'updated_at' => now(), 'created_at' => now()]
        );

        DB::table('settings')->updateOrInsert(
            ['key' => 'jam_pulang_mulai'],
            ['value' => '17:00', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        // Tidak mengembalikan ke 07:00 agar tidak merusak aturan baru.
    }
};
