<?php

namespace App\Support;

/**
 * Bantuan perhitungan geografis (rumus Haversine).
 * Dipakai untuk menghitung Jarak dari Kantor pada validasi absensi
 * dan untuk menampilkan histori Data Absen Masuk.
 */
class Geo
{
    /**
     * Jarak dalam meter antara dua koordinat (rumus Haversine).
     */
    public static function hitungJarak(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
