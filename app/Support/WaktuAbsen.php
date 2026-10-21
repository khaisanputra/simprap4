<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Waktu buka Absen Masuk dan Absen Pulang — jam mulai & jam selesai, WIB.
 * Contoh default: Masuk 08:00 - 08:30 WIB, Pulang 17:00 - 23:00 WIB.
 *
 * Nilai tersimpan di tabel `settings` (dapat diubah Admin pada halaman
 * Pengaturan Lokasi Kantor). Fallback: config/absensi.php.
 * Semua perbandingan menggunakan waktu server Laravel (Asia/Jakarta).
 *
 * Jam pulang dapat diatur admin via Lokasi Kantor (jam_pulang_mulai & jam_pulang_selesai).
 */
class WaktuAbsen
{

    protected static ?array $cache = null;

    /**
     * Ambil seluruh pengaturan jam absen (masuk | pulang).
     */
    public static function all(string $tipe = 'masuk'): array
    {
        $data = self::data();

        return [
            'mulai'   => $data[$tipe]['mulai'],
            'selesai' => $data[$tipe]['selesai'],
        ];
    }

    /**
     * Ambil jam mulai (format H:i) — masuk | pulang.
     */
    public static function mulai(string $tipe = 'masuk'): string
    {
        return self::all($tipe)['mulai'];
    }

    /**
     * Ambil jam selesai (format H:i) — masuk | pulang.
     */
    public static function selesai(string $tipe = 'masuk'): string
    {
        return self::all($tipe)['selesai'];
    }

    /**
     * Baca & cache seluruh pengaturan jam dari database (dibaca sekali per request).
     */
    protected static function data(): array
    {
        if (self::$cache === null) {
            $nilai = Setting::whereIn('key', [
                'jam_absen_mulai',
                'jam_absen_selesai',
                'jam_pulang_mulai',
                'jam_pulang_selesai',
                'jam_laporan_mulai',
                'jam_laporan_selesai',
            ])->pluck('value', 'key');

            self::$cache = [
                'masuk' => [
                    'mulai'   => $nilai['jam_absen_mulai']
                        ?? config('absensi.jam_mulai', '08:00'),
                    'selesai' => $nilai['jam_absen_selesai']
                        ?? config('absensi.jam_selesai', '08:30'),
                ],
                'pulang' => [
                    'mulai'   => $nilai['jam_pulang_mulai']
                        ?? config('absensi.jam_pulang_mulai', '17:00'),
                    'selesai' => $nilai['jam_pulang_selesai']
                        ?? config('absensi.jam_pulang_selesai', '23:00'),
                ],
                'laporan' => [
                    'mulai'   => $nilai['jam_laporan_mulai']
                        ?? config('absensi.jam_laporan_mulai', '08:00'),
                    'selesai' => $nilai['jam_laporan_selesai']
                        ?? config('absensi.jam_laporan_selesai', '23:59'),
                ],
            ];
        }

        return self::$cache;
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

    /**
     * Apakah sekarang berada dalam jendela waktu absen (WIB)?
     * Batas bawah & atas INKLUSIF:
     * masuk  : 08:00:00 s/d 08:30:00 valid — 08:30:01 ke atas ditolak.
     * pulang : 17:00:00 s/d 23:00:00 valid — 23:00:01 ke atas ditolak.
     */
    public static function dibuka(?\DateTimeInterface $waktu = null, string $tipe = 'masuk'): bool
    {
        $waktu = Carbon::parse($waktu ?: now())->setMicro(0);

        $mulaiTs   = $waktu->copy()->setTimeFromTimeString(self::mulai($tipe).':00');
        $selesaiTs = $waktu->copy()->setTimeFromTimeString(self::selesai($tipe).':00');

        return $waktu->gte($mulaiTs) && $waktu->lte($selesaiTs);
    }

    /**
     * Status jendela waktu: 'belum' | 'buka' | 'tutup' — masuk | pulang.
     */
    public static function status(?\DateTimeInterface $waktu = null, string $tipe = 'masuk'): string
    {
        $waktu = Carbon::parse($waktu ?: now())->setMicro(0);

        if (self::dibuka($waktu, $tipe)) {
            return 'buka';
        }

        $mulaiTs = $waktu->copy()->setTimeFromTimeString(self::mulai($tipe).':00');

        return $waktu->lt($mulaiTs) ? 'belum' : 'tutup';
    }

    /**
     * Tentukan status kehadiran Masuk vs Telat berdasarkan waktu server.
     * - waktu <= batas selesai masuk (08:30) => Masuk
     * - waktu > batas selesai => Telat
     * Dipanggil hanya setelah memastikan waktu >= jam mulai (bukan 'belum').
     */
    public static function tentukanStatusMasuk(?\DateTimeInterface $waktu = null): string
    {
        $waktu = Carbon::parse($waktu ?: now())->setMicro(0);
        $selesaiTs = $waktu->copy()->setTimeFromTimeString(self::selesai('masuk').':00');

        return $waktu->gt($selesaiTs) ? \App\Models\Attendance::STATUS_KEHADIRAN_TELAT : \App\Models\Attendance::STATUS_KEHADIRAN_MASUK;
    }

    /**
     * Apakah sekarang sudah lewat/melewati jam mulai Absen Pulang?
     * Digunakan untuk validasi Tidak Masuk (hanya setelah jam_pulang_mulai).
     */
    public static function sudahLewatMulaiPulang(?\DateTimeInterface $waktu = null): bool
    {
        $waktu = Carbon::parse($waktu ?: now())->setMicro(0);
        $mulai = $waktu->copy()->setTimeFromTimeString(self::mulai('pulang').':00');

        return $waktu->gte($mulai);
    }

    /**
     * Waktu minimal Absen Pulang — diambil dari setting jam_pulang_mulai.
     * Tidak lagi bergantung pada jam_masuk siswa.
     * Param $jamMasuk dipertahankan untuk kompatibilitas pemanggilan lama.
     */
    public static function pulangMulai(?string $jamMasuk = null): string
    {
        return self::mulai('pulang');
    }

    /**
     * Apakah sekarang berada dalam jendela Absen Pulang (WIB)?
     * Jendela diambil dari setting jam_pulang_mulai & jam_pulang_selesai (inklusif).
     * Param $jamMasuk diabaikan (kompatibilitas).
     * Semua perbandingan memakai waktu server Laravel (Asia/Jakarta).
     */
    public static function pulangDibuka(?\DateTimeInterface $waktu, ?string $jamMasuk = null): bool
    {
        return self::pulangStatus($waktu, $jamMasuk) === 'buka';
    }

    /**
     * Status jendela Absen Pulang: 'belum' | 'buka' | 'tutup'.
     *
     * - 'belum': sekarang < jam_pulang_mulai
     * - 'buka' : jam_pulang_mulai <= sekarang <= jam_pulang_selesai
     * - 'tutup': sekarang > jam_pulang_selesai
     *
     * Param $jamMasuk diabaikan — semua siswa memiliki waktu mulai sama dari setting.
     */
    public static function pulangStatus(?\DateTimeInterface $waktu, ?string $jamMasuk = null): string
    {
        return self::status($waktu, 'pulang');
    }
}
