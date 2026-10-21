<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Absensi (GPS / Lokasi Kantor)
    |--------------------------------------------------------------------------
    |
    | Nilai-nilai di bawah adalah FALLBACK. Sumber utama Lokasi Kantor
    | (nama, alamat, latitude, longitude, Radius Kantor) tersimpan di tabel
    | `settings` dan dapat diubah Admin melalui halaman Pengaturan Lokasi
    | Kantor (app/Support/LokasiKantor.php).
    |
    | Semua nilai fallback dapat di-override melalui file .env.
    |
    */

    // Nama kantor / instansi (fallback; ditampilkan pada halaman Absen Masuk).
    'nama_sekolah' => env('ABSENSI_NAMA_SEKOLAH', 'PT Sandya Network'),

    // Titik koordinat Lokasi Kantor (fallback). Default: Kantor Pacitan
    // (Jl. Jend. Sudirman No.3, Barang, Arjowinangun, Kec. Pacitan,
    // Kabupaten Pacitan, Jawa Timur 63516).
    'latitude' => (float) env('ABSENSI_LATITUDE', -8.198644745989196),
    'longitude' => (float) env('ABSENSI_LONGITUDE', 111.11795852827215),

    // Radius Kantor dalam meter (fallback; validasi memakai nilai dari database).
    'radius' => (float) env('ABSENSI_RADIUS', 100),

    // Jam buka Absen Masuk (WIB). Fallback; nilai utama di tabel `settings`.
    // Sesuai aturan baru: masuk mulai 08:00 WIB.
    'jam_mulai' => env('ABSENSI_JAM_MULAI', '08:00'),
    'jam_selesai' => env('ABSENSI_JAM_SELESAI', '08:30'),

    // Jam buka Absen Pulang (WIB). Fallback; nilai utama di tabel `settings`.
    'jam_pulang_mulai' => env('ABSENSI_JAM_PULANG_MULAI', '17:00'),
    'jam_pulang_selesai' => env('ABSENSI_JAM_PULANG_SELESAI', '23:00'),

    // Jam Laporan Sales & Teknisi (WIB). Fallback; nilai utama di tabel `settings`.
    'jam_laporan_mulai' => env('ABSENSI_JAM_LAPORAN_MULAI', '08:00'),
    'jam_laporan_selesai' => env('ABSENSI_JAM_LAPORAN_SELESAI', '23:59'),

    // GPS wajib: jika true, user harus mengirimkan lokasi valid untuk absen.
    'gps_wajib' => (bool) env('ABSENSI_GPS_WAJIB', true),

    // Akurasi GPS maksimal (meter). Jika akurasi lebih buruk dari ini,
    // absensi ditolak dengan pesan agar mendekati kantor / cek sinyal GPS.
    'max_accuracy' => (float) env('ABSENSI_MAX_ACCURACY', 150),

    // Foto absen (selfie) wajib: jika true, tombol absen baru aktif
    // setelah foto berhasil diambil dari kamera perangkat.
    'foto_wajib' => (bool) env('ABSENSI_FOTO_WAJIB', true),

    // Ukuran maksimal foto absen dalam kilobyte (10 MB).
    'max_foto_kb' => (int) env('ABSENSI_MAX_FOTO_KB', 10240),
];