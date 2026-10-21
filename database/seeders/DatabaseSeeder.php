<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Daftar 13 siswa asli PKL (sesuai instruksi, tanpa Ridho & tanpa demo).
     * Akun dibuat terlebih dahulu, lalu AttendanceSeeder menautkan
     * data absensi demo ke setiap siswa (user_id) sehingga nama pada
     * record absensi selalu sama dengan nama pengguna.
     * Password default: Siswa123 (sesuai Restore14SiswaSeeder).
     */
    protected array $siswa = [
        ['username' => 'Rehan',  'user_id' => 'G1 - INT0040', 'nama' => 'Khaisan Raihan Saputra',          'email' => 'KhaisanRaihanSaputra@gmail.com'],
        ['username' => 'Fagus',  'user_id' => 'G1 - INT0041', 'nama' => 'Fagus Ridwan',                    'email' => 'FagusRidwan@gmail.com'],
        ['username' => 'Nauval', 'user_id' => 'G1 - INT0034', 'nama' => 'Nauval Vedra Pratama',            'email' => 'NauvalVedraPratama@gmail.com'],
        ['username' => 'Fadli',  'user_id' => 'G1 - INT0032', 'nama' => 'Fadli Rizky Saputra',             'email' => 'FadliRizkySaputra@gmail.com'],
        ['username' => 'Nevril', 'user_id' => 'G1 - INT0033', 'nama' => 'Nevril Nur Irwan Khoiriansah',    'email' => 'NevrilNurIrwanKhoiriansah@gmail.com'],
        ['username' => 'Riska',  'user_id' => 'G1 - INT0039', 'nama' => 'Riska Sahwalani',                 'email' => 'RiskaSahwalani@gmail.com'],
        ['username' => 'Huda',   'user_id' => 'G1 - INT0035', 'nama' => 'Rifqi Fahruzian Anurul Huda',     'email' => 'RifqiFahruzianAnurulHuda@gmail.com'],
        ['username' => 'Marvel', 'user_id' => 'G1 - INT0031', 'nama' => 'Marvel Rasya Pramudya',           'email' => 'MarvelRasyaPramudya@gmail.com'],
        ['username' => 'Hanung', 'user_id' => 'G1 - INT0030', 'nama' => 'Hanung Aprilio Gunawan',          'email' => 'HanungAprilioGunawan@gmail.com'],
        ['username' => 'Mira',   'user_id' => 'G1 - INT0038', 'nama' => 'Almira Kusumaningrum',            'email' => 'AlmiraKusumaningrum@gmail.com'],
        ['username' => 'Nadin',  'user_id' => 'G1 - INT0037', 'nama' => 'Nadina Ainur Pratiwi',            'email' => 'NadinaAinurPratiwi@gmail.com'],
        ['username' => 'Zadi',   'user_id' => 'G1 - INT0042', 'nama' => 'Muhammad Khoiruzzadi',            'email' => 'MuhammadKhoiruzzadi@gmail.com'],
        ['username' => 'Putri',  'user_id' => 'G1 - INT0036', 'nama' => 'Putri Setyaningsih',              'email' => 'PutriSetyaningsih@gmail.com'],
    ];

    public function run(): void
    {
        // Akun admin (role = admin).
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                // Email admin akun resmi.
                'email'    => env('ADMIN_EMAIL', 'daunbot03@gmail.com'),
                'password' => Hash::make('password123'),
                'nama'     => 'Administrator',
                'role'     => 'admin',
                'status'   => 'Aktif',
                // admin tidak wajib user_id, biarkan null
                'user_id'  => null,
            ]
        );

        // Akun siswa PKL (role = siswa, status = Aktif).
        foreach ($this->siswa as $s) {
            User::updateOrCreate(
                ['username' => $s['username']],
                [
                    'user_id'  => $s['user_id'],
                    'nama'     => $s['nama'],
                    'email'    => $s['email'],
                    'password' => Hash::make('Siswa123'),
                    'role'     => 'siswa',
                    'status'   => 'Aktif',
                ]
            );
        }

        // Bersihkan akun demo/sisa yang mungkin masih ada dari seeder lama
        // (Khaisan Putra, Bagus Ridwan, Alya Rahma, Dimas Prasetyo, Salsabila Nuri, Raka Aditya, Putri Ayu, Fajar Nugroho, Ridho, dll)
        // Hanya untuk idempotency saat seeder dijalankan tanpa fresh; migrate:fresh sudah drop semua.
        $targetUsernames = array_column($this->siswa, 'username');
        // Hapus siswa demo yang bukan bagian 13 target (role siswa, username tidak ada di target)
        User::where('role', 'siswa')
            ->whereNotIn('username', $targetUsernames)
            ->delete();
        // Hapus juga Ridho berdasarkan email jika ada (jaga duplikat)
        User::where('email', 'RidhoVidianPutra@gmail.com')
            ->whereNotIn('username', $targetUsernames)
            ->delete();

        // Data absensi demo dibuat setelah pengguna terdaftar, sehingga
        // dapat ditautkan ke user_id dan memakai nama pengguna terbaru.
        $this->call([
            AttendanceSeeder::class,
        ]);
    }
}
