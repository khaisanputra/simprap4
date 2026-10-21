<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporans')->cascadeOnDelete();
            $table->string('foto');
            $table->string('foto_original_name')->nullable();
            $table->string('foto_mime_type')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('laporan_id');
        });

        // Migrasi data foto lama (single) ke tabel baru untuk backward compat
        if (Schema::hasTable('laporans') && Schema::hasTable('laporan_fotos')) {
            $laporans = DB::table('laporans')->whereNotNull('foto')->get();
            foreach ($laporans as $lap) {
                $exists = DB::table('laporan_fotos')->where('laporan_id', $lap->id)->exists();
                if ($exists) continue;
                DB::table('laporan_fotos')->insert([
                    'laporan_id' => $lap->id,
                    'foto' => $lap->foto,
                    'foto_original_name' => $lap->foto_original_name,
                    'foto_mime_type' => $lap->foto_mime_type,
                    'sort_order' => 0,
                    'created_at' => $lap->created_at ?? now(),
                    'updated_at' => $lap->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_fotos');
    }
};
