<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan pengaturan jam buka Absen Pulang (17:00 - 23:00 WIB).
     * Idempotent (updateOrInsert) agar aman dijalankan berulang.
     */
    public function up(): void
    {
        $data = [
            'jam_pulang_mulai'   => '17:00',
            'jam_pulang_selesai' => '23:00',
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
        DB::table('settings')->whereIn('key', ['jam_pulang_mulai', 'jam_pulang_selesai'])->delete();
    }
};
