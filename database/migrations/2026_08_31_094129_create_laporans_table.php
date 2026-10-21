<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('laporans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('tanggal_laporan');
            $table->text('teks_laporan');
            $table->string('foto')->nullable();
            $table->string('foto_original_name')->nullable();
            $table->string('foto_mime_type')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tanggal_laporan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporans');
    }
};
