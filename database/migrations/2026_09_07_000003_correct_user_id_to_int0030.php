<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Final mapping User ID INT0030–INT0042 (sudah benar di database produksi).
     * - Hanung INT0030, Marvel INT0031, ... Zadi INT0042
     * - Migration ini idempotent: jika database sudah memiliki mapping final,
     *   tidak melakukan perubahan tambahan (hanya memastikan admin tetap null).
     * - Aman terhadap unique constraint: kosongkan dulu baru isi hanya untuk baris yang perlu koreksi.
     * - Tidak mengubah users.id, username, email, password, role, status.
     * - Tidak menyentuh attendances.user_id / laporans.user_id / monthly_statistics.user_id (FK ke users.id).
     */
    public function up(): void
    {
        $finalMapping = [
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

        // Cek apakah mapping sudah final — jika ya, cukup pastikan admin null dan selesai.
        $existing = DB::table('users')
            ->whereIn('id', array_keys($finalMapping))
            ->pluck('user_id', 'id');

        $needsUpdate = false;
        foreach ($finalMapping as $id => $expected) {
            if (!isset($existing[$id]) || $existing[$id] !== $expected) {
                $needsUpdate = true;
                break;
            }
        }

        // Selalu pastikan admin tidak memiliki User ID G1
        $ensureAdminNull = function () {
            DB::table('users')->where('id', 11)->update(['user_id' => null]);
        };

        if (!$needsUpdate) {
            $ensureAdminNull();
            return;
        }

        // Hanya update baris yang belum sesuai, hindari collision unique dengan null-kan dulu
        $idsToFix = [];
        foreach ($finalMapping as $id => $expected) {
            if (!isset($existing[$id]) || $existing[$id] !== $expected) {
                $idsToFix[] = $id;
            }
        }

        if (!empty($idsToFix)) {
            DB::table('users')->whereIn('id', $idsToFix)->update(['user_id' => null]);
            foreach ($idsToFix as $id) {
                DB::table('users')->where('id', $id)->update(['user_id' => $finalMapping[$id]]);
            }
        }

        $ensureAdminNull();
    }

    /**
     * Reverse: TIDAK mengembalikan ke mapping lama INT0031–INT0043 yang salah.
     * Rollback hanya mengosongkan user_id untuk 13 siswa tersebut (kembali ke null),
     * sehingga tidak merusak mapping final INT0030–INT0042 dan tidak menimbulkan duplikasi.
     * Admin tetap null. Tidak menyentuh users.id atau tabel relasi lain.
     */
    public function down(): void
    {
        $ids = [93, 92, 88, 89, 87, 91, 97, 95, 94, 90, 85, 86, 96];

        // Kosongkan saja, jangan isi kembali ke INT0031 lama
        DB::table('users')->whereIn('id', $ids)->update(['user_id' => null]);
        DB::table('users')->where('id', 11)->update(['user_id' => null]);
    }
};
