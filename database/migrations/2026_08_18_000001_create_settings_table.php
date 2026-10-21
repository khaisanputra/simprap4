<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel settings (key-value) untuk menyimpan Pengaturan Lokasi Kantor
     * yang dapat diubah admin: nama kantor, alamat, latitude, longitude,
     * dan Radius Kantor. Source of truth validasi absensi.
     *
     * Default diambil dari config/absensi.php agar sistem tetap jalan
     * saat pertama kali dipasang tanpa mengubah kode.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('settings')->insert([
            [
                'key'       => 'lokasi_kantor_nama',
                'value'     => config('absensi.nama_sekolah', 'PT Sandya Network'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'       => 'lokasi_kantor_alamat',
                'value'     => 'Jl. Jend. Sudirman No.3, Barang, Arjowinangun, Kec. Pacitan, Kabupaten Pacitan, Jawa Timur 63516',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'       => 'lokasi_kantor_latitude',
                'value'     => (string) config('absensi.latitude', -6.2658),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'       => 'lokasi_kantor_longitude',
                'value'     => (string) config('absensi.longitude', 106.7520),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'       => 'lokasi_kantor_radius',
                'value'     => (string) config('absensi.radius', 300),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};