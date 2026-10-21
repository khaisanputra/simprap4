<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Seed data absensi demo untuk pengguna siswa yang terdaftar.
     *
     * Nama pada record absensi diambil dari nama pengguna saat ini
     * (tabel users) dan dihubungkan lewat user_id, agar nama pada
     * halaman "Data Absen Masuk" selalu sama dengan nama pengguna.
     */
    public function run(): void
    {
        // Gunakan tanggal hari ini agar data tampil pada dashboard default.
        // $today = now()->toDateString();

        // $siswa = User::query()
        //     ->where('role', 'siswa')
        //     ->orderBy('id')
        //     ->get();

        // $jamMasuk = [
        //     '08:00:00', '08:05:00', '08:12:00', '08:20:00',
        //     '08:30:00', '08:33:00', '08:40:00', '08:45:00',
        //     '08:10:00', '08:25:00', '08:35:00', '08:50:00',
        // ];

        // foreach ($siswa as $index => $user) {
        //     $jam = $jamMasuk[$index % count($jamMasuk)];

        //     $attributes = [
        //         'user_id'    => $user->id,
        //         'nama'       => $user->nama,
        //         'status'     => 'Masuk',
        //         'tgl_masuk'  => $today,
        //         'jam_masuk'  => $jam,
        //         'tgl_pulang' => null,
        //         'jam_pulang' => null,
        //     ];

        //     // Variasi status pulang agar tidak semua siswa berstatus "Masuk".
        //     if ($index % 3 === 1) {
        //         $attributes['status']     = 'Pulang';
        //         $attributes['tgl_pulang'] = $today;
        //         $attributes['jam_pulang'] = '16:10:00';
        //     } elseif ($index % 3 === 2) {
        //         $attributes['status'] = 'Belum Pulang';
        //     }

        //     // Hanya membuat data demo bila belum ada absensi hari ini
        //     // untuk siswa tersebut; absensi asli tidak pernah ditimpa.
        //     Attendance::firstOrCreate(
        //         ['user_id' => $user->id, 'tgl_masuk' => $today],
        //         $attributes
        //     );
        // }
    }
}
