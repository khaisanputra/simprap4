<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom path foto & jarak dari kantor pada tabel attendances.
     * Data existing tidak diubah: kolom baru nullable.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('accuracy');
            $table->decimal('jarak', 10, 2)->nullable()->after('foto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['foto', 'jarak']);
        });
    }
};