# TODO: Perbaikan Persentase Kehadiran Dashboard + Role-based Access

## Langkah
- [x] 1. Migration: tambah kolom `role`, `nama`, `status` ke tabel `users`
- [x] 2. Update Model `User`: $fillable, $casts, scope `siswaAktif()`
- [x] 3. Update Seeder: seed akun admin (role=admin) + akun siswa (role=siswa, status=Aktif)
- [x] 4. Update LoginController: simpan `role` ke session saat login
- [x] 5. Update DashboardController: hitung `$totalSiswa` & perbaiki rumus persentase (server-side)
- [x] 6. Update Dashboard Blade: tampilkan `$totalSiswa`, sembunyikan Edit/Export untuk siswa
- [x] 7. Update dashboard.js: hapus perhitungan ulang persentase (best-practice MVC)
- [x] 8. Buat middleware role + proteksi route
- [x] 9. Jalankan migrasi & seeder, uji
