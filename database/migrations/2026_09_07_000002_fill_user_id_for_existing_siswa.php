<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Isi User ID FINAL untuk 13 siswa yang sudah ada.
     * Mapping berdasarkan users.id (bukan nama/username/urutan).
     * Admin (id 11) JANGAN diberi User ID G1.
     * Tidak mengubah users.id, username, email, password, role, status.
     * Tidak mengubah relasi attendances/laporans/monthly_statistics.
     */
    public function up(): void
    {
        $mapping = [
            93 => 'G1 - INT0030', // Hanung Aprilio Gunawan
            92 => 'G1 - INT0031', // Marvel Rasya Pramudya
            88 => 'G1 - INT0032', // Fadli Rizky Saputra
            89 => 'G1 - INT0033', // Nevri(l) Nur Irwan Khoiriansah
            87 => 'G1 - INT0034', // Nauval Vedra Pratama
            91 => 'G1 - INT0035', // Rifqi Fahruzian Anurul Huda
            97 => 'G1 - INT0036', // Putri Setyaningsih
            95 => 'G1 - INT0037', // Nadina Ainur Pratiwi
            94 => 'G1 - INT0038', // Almira Kusumaningrum
            90 => 'G1 - INT0039', // Riska Sahwalani
            85 => 'G1 - INT0040', // Khaisan Raihan Saputra
            86 => 'G1 - INT0041', // Fagus Ridwan
            96 => 'G1 - INT0042', // Muhammad Khoiruzzadi
        ];

        foreach ($mapping as $id => $userId) {
            DB::table('users')->where('id', $id)->update(['user_id' => $userId]);
        }

        // Pastikan admin tidak memiliki User ID G1
        DB::table('users')->where('id', 11)->update(['user_id' => null]);
    }

    /**
     * Reverse: kembalikan user_id menjadi null untuk 13 siswa tersebut.
     */
    public function down(): void
    {
        $ids = [93, 92, 88, 89, 87, 91, 97, 95, 94, 90, 85, 86, 96];
        DB::table('users')->whereIn('id', $ids)->update(['user_id' => null]);
    }
};
