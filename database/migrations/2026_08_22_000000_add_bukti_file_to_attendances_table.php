<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom bukti pendukung untuk Izin/Dispensasi/Sakit.
     * File bersifat opsional, hanya untuk memperkuat pengajuan.
     * Masuk tidak menggunakan bukti.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('bukti_file')->nullable()->after('keterangan');
            $table->string('bukti_original_name')->nullable()->after('bukti_file');
            $table->string('bukti_mime_type')->nullable()->after('bukti_original_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['bukti_file', 'bukti_original_name', 'bukti_mime_type']);
        });
    }
};
