<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom GPS & relasi user pada tabel attendances.
     * Aman untuk database existing: seluruh kolom baru nullable,
     * kolom lama tidak diubah dan data lama tidak terhapus.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->double('latitude')->nullable()->after('jam_pulang');
            $table->double('longitude')->nullable()->after('latitude');
            $table->double('accuracy')->nullable()->after('longitude');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn(['user_id', 'latitude', 'longitude', 'accuracy']);
        });
    }
};