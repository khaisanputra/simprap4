<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Pengaturan Lokasi Kantor (sumber validasi GPS absensi).
 *
 * Nilai yang tersimpan di tabel `settings` adalah sumber utama
 * (dapat diubah Admin melalui halaman Pengaturan Lokasi Kantor).
 * Jika belum tersimpan, fallback ke config/absensi.php.
 */
class LokasiKantor
{
    protected static ?array $cache = null;

    /**
     * Ambil seluruh pengaturan lokasi kantor.
     */
    public static function all(): array
    {
        if (self::$cache === null) {
            $keys = [
                'lokasi_kantor_nama',
                'lokasi_kantor_alamat',
                'lokasi_kantor_latitude',
                'lokasi_kantor_longitude',
                'lokasi_kantor_radius',
            ];

            $nilai = Setting::whereIn('key', $keys)->pluck('value', 'key');

            self::$cache = [
                'nama'      => $nilai['lokasi_kantor_nama']
                    ?? config('absensi.nama_sekolah', 'PT Sandya Network'),
                'alamat'    => $nilai['lokasi_kantor_alamat']
                    ?? 'Jl. Jend. Sudirman No.3, Barang, Arjowinangun, Kec. Pacitan, Kabupaten Pacitan, Jawa Timur 63516',
                'latitude'  => (float) ($nilai['lokasi_kantor_latitude']
                    ?? config('absensi.latitude', -6.2658)),
                'longitude' => (float) ($nilai['lokasi_kantor_longitude']
                    ?? config('absensi.longitude', 106.7520)),
                'radius'    => (float) ($nilai['lokasi_kantor_radius']
                    ?? config('absensi.radius', 300)),
            ];
        }

        return self::$cache;
    }

    /**
     * Ambil satu nilai pengaturan.
     */
    public static function get(string $key)
    {
        return self::all()[$key] ?? null;
    }

    /**
     * Simpan/sesuaikan satu nilai pengaturan ke database.
     */
    public static function set(string $key, $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        self::flush();
    }

    /**
     * Kosongkan cache agar pengaturan berikutnya dibaca ulang dari database.
     */
    public static function flush(): void
    {
        self::$cache = null;
    }
}