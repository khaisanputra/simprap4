<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Restore14SiswaSeeder extends Seeder
{
    /**
     * Daftar 13 siswa asli yang harus dipulihkan (sesuai instruksi, tanpa Ridho).
     * Password: Siswa123 (hashed via Hash::make / casts hashed)
     * Sekarang sinkron dengan DatabaseSeeder (13 siswa, bukan 14).
     */
    private array $siswa = [
        ['nama' => 'Khaisan Raihan Saputra',       'username' => 'Rehan',  'user_id' => 'G1 - INT0040', 'email' => 'KhaisanRaihanSaputra@gmail.com'],
        ['nama' => 'Fagus Ridwan',                 'username' => 'Fagus',  'user_id' => 'G1 - INT0041', 'email' => 'FagusRidwan@gmail.com'],
        ['nama' => 'Nauval Vedra Pratama',         'username' => 'Nauval', 'user_id' => 'G1 - INT0034', 'email' => 'NauvalVedraPratama@gmail.com'],
        ['nama' => 'Fadli Rizky Saputra',          'username' => 'Fadli',  'user_id' => 'G1 - INT0032', 'email' => 'FadliRizkySaputra@gmail.com'],
        ['nama' => 'Nevril Nur Irwan Khoiriansah', 'username' => 'Nevril', 'user_id' => 'G1 - INT0033', 'email' => 'NevrilNurIrwanKhoiriansah@gmail.com'],
        ['nama' => 'Riska Sahwalani',              'username' => 'Riska',  'user_id' => 'G1 - INT0039', 'email' => 'RiskaSahwalani@gmail.com'],
        ['nama' => 'Rifqi Fahruzian Anurul Huda',  'username' => 'Huda',   'user_id' => 'G1 - INT0035', 'email' => 'RifqiFahruzianAnurulHuda@gmail.com'],
        ['nama' => 'Marvel Rasya Pramudya',        'username' => 'Marvel', 'user_id' => 'G1 - INT0031', 'email' => 'MarvelRasyaPramudya@gmail.com'],
        ['nama' => 'Hanung Aprilio Gunawan',       'username' => 'Hanung', 'user_id' => 'G1 - INT0030', 'email' => 'HanungAprilioGunawan@gmail.com'],
        ['nama' => 'Almira Kusumaningrum',         'username' => 'Mira',   'user_id' => 'G1 - INT0038', 'email' => 'AlmiraKusumaningrum@gmail.com'],
        ['nama' => 'Nadina Ainur Pratiwi',         'username' => 'Nadin',  'user_id' => 'G1 - INT0037', 'email' => 'NadinaAinurPratiwi@gmail.com'],
        ['nama' => 'Muhammad Khoiruzzadi',         'username' => 'Zadi',   'user_id' => 'G1 - INT0042', 'email' => 'MuhammadKhoiruzzadi@gmail.com'],
        ['nama' => 'Putri Setyaningsih',           'username' => 'Putri',  'user_id' => 'G1 - INT0036', 'email' => 'PutriSetyaningsih@gmail.com'],
    ];

    /**
     * Daftar akun dummy yang dibuat oleh test AI sebelumnya dan harus dihapus.
     * Tidak menghapus admin. Termasuk Ridho yang sekarang tidak boleh ada.
     */
    private array $dummyUsernames = [
        'ahmad','budi','citra','deni','eko','fajar',
        'siswa7','siswa8','siswa9','siswa10','siswa11','siswa12','siswa13','siswa14','siswa15',
        // dummy lama dari test awal (jika ada)
        'siswaa','siswa1','siswa2','siswa3','siswa4','siswa5','siswa6',
        'Khaisan Putra','Bagus Ridwan','Alya Rahma','Dimas Prasetyo','Salsabila Nuri','Raka Aditya','Putri Ayu','Fajar Nugroho',
        'Ridho','Ridho Vidian Putra',
    ];

    public function run(): void
    {
        // 1. Hapus akun dummy (hanya yang bukan admin dan bukan 13 siswa target)
        $targetUsernames = array_column($this->siswa, 'username');
        $targetEmails = array_column($this->siswa, 'email');

        // Hapus dummy yang ada
        $deletedDummy = User::whereIn('username', $this->dummyUsernames)
            ->where('role', 'siswa')
            ->whereNotIn('username', $targetUsernames)
            ->delete();
        $this->command?->info("Hapus dummy: $deletedDummy akun");

        // Hapus juga dummy berdasarkan email yang tidak ada di target (jaga duplikat email)
        // Hanya hapus jika username tidak ada di target
        $deletedByEmail = User::whereIn('email', [
                'ahmad@test.com','budi@test.com','citra@test.com','deni@test.com','eko@test.com','fajar@test.com',
                'siswa7@test.com','siswa8@test.com','siswa9@test.com','siswa10@test.com','siswa11@test.com','siswa12@test.com','siswa13@test.com','siswa14@test.com','siswa15@test.com',
                'RidhoVidianPutra@gmail.com',
            ])
            ->whereNotIn('username', $targetUsernames)
            ->where('role', 'siswa')
            ->delete();
        if ($deletedByEmail) {
            $this->command?->info("Hapus dummy by email: $deletedByEmail akun");
        }

        // Hapus Ridho secara eksplisit jika masih ada (username atau email)
        $deletedRidho = User::where('username', 'Ridho')
            ->orWhere('email', 'RidhoVidianPutra@gmail.com')
            ->whereNotIn('username', $targetUsernames)
            ->delete();
        if ($deletedRidho) {
            $this->command?->info("Hapus Ridho: $deletedRidho akun");
        }

        // 2. Buat / perbaiki 13 akun siswa (idempotent)
        foreach ($this->siswa as $s) {
            // Cek duplikat: cari by username atau email atau user_id
            $existingByUsername = User::where('username', $s['username'])->first();
            $existingByEmail = User::where('email', $s['email'])->first();
            $existingByUserId = User::where('user_id', $s['user_id'])->first();

            $user = $existingByUsername ?: ($existingByEmail ?: $existingByUserId);

            if ($user) {
                // Jangan ubah admin (pastikan role bukan admin)
                if ($user->role === 'admin') {
                    $this->command?->warn("Skip admin dengan username/email {$s['username']}/{$s['email']}");
                    continue;
                }
                $user->update([
                    'nama'     => $s['nama'],
                    'username' => $s['username'],
                    'user_id'  => $s['user_id'],
                    'email'    => $s['email'],
                    'password' => Hash::make('Siswa123'),
                    'role'     => 'siswa',
                    'status'   => 'Aktif',
                ]);
                $this->command?->info("Update siswa: {$s['username']} ({$s['email']})");
            } else {
                User::create([
                    'nama'     => $s['nama'],
                    'username' => $s['username'],
                    'user_id'  => $s['user_id'],
                    'email'    => $s['email'],
                    'password' => Hash::make('Siswa123'),
                    'role'     => 'siswa',
                    'status'   => 'Aktif',
                ]);
                $this->command?->info("Buat siswa: {$s['username']} ({$s['email']})");
            }
        }

        // 3. Pastikan admin tetap ada dan tidak berubah
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $this->command?->info("Admin tetap: {$admin->username} ({$admin->email}) - tidak diubah");
        } else {
            $this->command?->warn("Tidak ada admin ditemukan!");
        }

        // 4. Ringkasan
        $total = User::count();
        $siswaCount = User::where('role', 'siswa')->where('status', 'Aktif')->count();
        $adminCount = User::where('role', 'admin')->count();
        $this->command?->info("Total users: $total (Admin: $adminCount, Siswa Aktif: $siswaCount)");
    }
}
