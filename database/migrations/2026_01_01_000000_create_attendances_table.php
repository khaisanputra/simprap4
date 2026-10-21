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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('status')->default('Masuk'); // Masuk | Pulang | Belum Pulang
            $table->date('tgl_masuk')->nullable();
            $table->time('jam_masuk')->nullable();
            $table->date('tgl_pulang')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
