<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 20)->nullable()->after('user_id');
            $table->string('tempat_lahir', 100)->nullable()->after('nik');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            $table->text('alamat')->nullable()->after('tanggal_lahir');
            $table->string('no_telepon', 20)->nullable()->after('alamat');
            $table->string('asal_sekolah', 150)->nullable()->after('no_telepon');
            $table->text('alamat_sekolah')->nullable()->after('asal_sekolah');
            $table->string('jenis_identitas_sekolah', 10)->nullable()->after('alamat_sekolah');
            $table->string('nomor_identitas_sekolah', 30)->nullable()->after('jenis_identitas_sekolah');
            $table->date('pkl_mulai')->nullable()->after('nomor_identitas_sekolah');
            $table->date('pkl_selesai')->nullable()->after('pkl_mulai');
            $table->string('ktp_path')->nullable()->after('pkl_selesai');
            $table->string('kartu_pelajar_path')->nullable()->after('ktp_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nik',
                'tempat_lahir',
                'tanggal_lahir',
                'alamat',
                'no_telepon',
                'asal_sekolah',
                'alamat_sekolah',
                'jenis_identitas_sekolah',
                'nomor_identitas_sekolah',
                'pkl_mulai',
                'pkl_selesai',
                'ktp_path',
                'kartu_pelajar_path',
            ]);
        });
    }
};
