<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buat tabel laporan_sales terpisah dari laporans (Teknisi).
     * Sales adalah fitur TAMBAHAN, bukan pengganti Teknisi.
     */
    public function up(): void
    {
        Schema::create('laporan_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('tanggal');
            $table->string('nama_customer', 255);
            $table->string('no_telp', 30)->nullable();
            $table->text('alamat_customer');
            $table->string('provider', 50);
            $table->text('kelebihan')->nullable();
            $table->text('kekurangan')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tanggal']);
            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_sales');
    }
};
