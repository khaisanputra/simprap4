<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel notifikasi khusus ADMIN (Izin, Dispensasi, Sakit, Alpha).
     * Tidak mengubah tabel existing. Penyimpanan aman, anti-duplikat per siswa/tanggal/jenis.
     */
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index()->comment('izin/dispensasi/sakit/alpha');
            $table->string('title', 150);
            $table->text('message');
            $table->unsignedBigInteger('student_user_id')->nullable()->index();
            $table->string('student_name', 150);
            $table->date('tanggal')->index();
            $table->unsignedBigInteger('reference_id')->nullable()->index()->comment('attendance id jika ada');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            // Anti-duplikat: 1 siswa + 1 tanggal + 1 jenis = 1 notifikasi
            $table->unique(['type', 'student_user_id', 'tanggal'], 'admin_notif_unique');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
