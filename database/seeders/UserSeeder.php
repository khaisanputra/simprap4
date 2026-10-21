<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'username' => 'admin',
            // Email admin akun resmi.
            'email' => env('ADMIN_EMAIL', 'daunbot03@gmail.com'),
            'password' => Hash::make('password123'),
            'nama' => 'Administrator',
            'role' => 'admin',
            'status' => 'Aktif',
        ]);
    }
}
