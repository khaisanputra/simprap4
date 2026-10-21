<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AbsenController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataAbsenMasukController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LaporanSalesCollectController;
use App\Http\Controllers\LaporanSalesController;
use App\Http\Controllers\LaporanSalesFormController;
use App\Http\Controllers\LokasiKantorController;
use App\Http\Controllers\RekapStatistikController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.process');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/absen-masuk', [AbsenController::class, 'index'])->name('absen.masuk');
    Route::post('/absen-masuk', [AbsenController::class, 'store'])->middleware('role:siswa')->name('absen.masuk.store');
    Route::get('/absen-pulang', [AbsenController::class, 'pulang'])->name('absen.pulang');
    Route::post('/absen-pulang', [AbsenController::class, 'pulangStore'])->middleware('role:siswa')->name('absen.pulang.store');

    // Laporan Sales — WAJIB didefinisikan SEBELUM wildcard /laporan/{laporan} agar tidak bentrok
    // Tahap 1: Form Laporan Sales (teks + foto) — mirip Teknisi — HANYA FORM
    Route::get('/laporan/sales', [LaporanSalesFormController::class, 'index'])->name('laporan.sales.index');
    Route::post('/laporan/sales', [LaporanSalesFormController::class, 'store'])->name('laporan.sales.store');
    // Data Sales — halaman terpisah (filter, pagination, detail, hapus, export)
    Route::get('/laporan/sales/data', [LaporanSalesController::class, 'index'])->name('laporan.sales.data');
    Route::get('/laporan/sales/export-excel', [LaporanSalesController::class, 'exportExcel'])->name('laporan.sales.export');
    // Tahap 2: Collect Data Sales (1-10 customer) — route terpisah
    Route::get('/laporan/sales/{laporan}/collect', [LaporanSalesCollectController::class, 'index'])->name('laporan.sales.collect');
    Route::post('/laporan/sales/{laporan}/collect', [LaporanSalesCollectController::class, 'store'])->name('laporan.sales.collect.store');
    // Edit laporan utama Sales (1 laporan/hari, edit record yang sama)
    Route::get('/laporan/sales/{laporan}/edit', [LaporanSalesFormController::class, 'edit'])->name('laporan.sales.edit');
    Route::put('/laporan/sales/{laporan}', [LaporanSalesFormController::class, 'update'])->name('laporan.sales.update');
    // Data Sales detail/hapus (tetap via LaporanSalesController) — setelah static routes
    Route::get('/laporan/sales/{sales}', [LaporanSalesController::class, 'show'])->name('laporan.sales.show');
    Route::delete('/laporan/sales/{sales}', [LaporanSalesController::class, 'destroy'])->name('laporan.sales.destroy');

    // Laporan Teknisi — Siswa & Admin (auth only, authorization di controller) — TETAP seperti semula
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::post('/laporan', [LaporanController::class, 'store'])->name('laporan.store');
    // Per-laporan routes (detail, foto, print, hapus) — Print tetap untuk Save as PDF via browser, Excel dihapus sesuai permintaan
    // Edit Teknisi: GET edit sebelum show agar konsisten; PUT update untuk simpan perubahan
    Route::get('/laporan/{laporan}/edit', [LaporanController::class, 'edit'])->name('laporan.edit');
    Route::put('/laporan/{laporan}', [LaporanController::class, 'update'])->name('laporan.update');
    Route::get('/laporan/{laporan}', [LaporanController::class, 'show'])->name('laporan.show');
    Route::get('/laporan/{laporan}/foto', [LaporanController::class, 'lihatFoto'])->name('laporan.foto');
    Route::get('/laporan/{laporan}/foto/{foto}', [LaporanController::class, 'lihatFotoById'])->name('laporan.foto.show');
    Route::get('/laporan/{laporan}/print', [LaporanController::class, 'print'])->name('laporan.print');
    Route::delete('/laporan/{laporan}', [LaporanController::class, 'destroy'])->name('laporan.destroy');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Lihat bukti pendukung (JPG/PNG/PDF) via controller tanpa storage:link (kompatibel InfinityFree)
    Route::get('/bukti/{attendance}/lihat', [AbsenController::class, 'lihatBukti'])->name('bukti.lihat');
    // Lihat foto absensi (Masuk/Pulang) via controller tanpa storage:link (kompatibel InfinityFree)
    Route::get('/foto/{attendance}/lihat', [AbsenController::class, 'lihatFoto'])->name('foto.lihat');

    // Halaman cetak bersih (hanya tabel) untuk semua pengguna yang login.
    Route::get('/dashboard/print', [DashboardController::class, 'print'])->name('dashboard.print');

    // Rekap Statistik Bulanan — Siswa (arsip milik sendiri, wajib auth)
    Route::get('/rekap-saya', [RekapStatistikController::class, 'siswaIndex'])->name('rekap.siswa');
    // Detail per tanggal (klik sel kalender) — siswa & admin (auth, cek hak di controller)
    Route::get('/rekap/detail', [RekapStatistikController::class, 'detail'])->name('rekap.detail');
});

// Khusus admin: aksi export data absensi (Excel) & manajemen Menu User — PDF download dihapus, pakai Print → Save as PDF.
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Notifikasi Admin — hanya untuk admin (bell kanan atas → dropdown existing)
    Route::get('/admin/notifikasi/unread-count', [AdminNotificationController::class, 'unreadCount'])->name('admin.notifikasi.unreadCount');
    Route::get('/admin/notifikasi/dropdown', [AdminNotificationController::class, 'dropdown'])->name('admin.notifikasi.dropdown');
    Route::post('/admin/notifikasi/{id}/read', [AdminNotificationController::class, 'markRead'])->name('admin.notifikasi.read');
    Route::post('/admin/notifikasi/read-all', [AdminNotificationController::class, 'markAllRead'])->name('admin.notifikasi.readAll');

    Route::get('/dashboard/export-excel', [DashboardController::class, 'exportExcel'])->name('dashboard.export.excel');

    // Data Absen (hanya admin): monitoring histori absensi.
    Route::get('/data-absen-masuk', [DataAbsenMasukController::class, 'index'])->name('data-absen-masuk.index');
    Route::get('/data-absen-masuk/print', [DataAbsenMasukController::class, 'print'])->name('data-absen-masuk.print');
    Route::get('/data-absen-masuk/export-excel', [DataAbsenMasukController::class, 'exportExcel'])->name('data-absen-masuk.export.excel');

    // Menu User (hanya admin).
    Route::get('/menu-user', [UserController::class, 'index'])->name('users.index');
    Route::get('/menu-user/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/menu-user', [UserController::class, 'store'])->name('users.store');
    Route::get('/menu-user/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/menu-user/{user}/dokumen/{jenis}', [UserController::class, 'document'])->name('users.document');
    Route::get('/menu-user/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/menu-user/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/menu-user/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::put('/menu-user/{user}/password', [UserController::class, 'updatePassword'])->name('users.updatePassword');

    // Pengaturan Lokasi Kantor (hanya admin).
    Route::get('/lokasi-kantor', [LokasiKantorController::class, 'index'])->name('lokasi-kantor.index');
    Route::post('/lokasi-kantor', [LokasiKantorController::class, 'update'])->name('lokasi-kantor.update');

    // Rekap Statistik Bulanan — Admin (seluruh siswa, filter bulan/tahun/cari)
    Route::get('/rekap-statistik', [RekapStatistikController::class, 'adminIndex'])->name('rekap.admin');
    Route::get('/rekap-statistik/export-excel', [RekapStatistikController::class, 'exportExcelCalendar'])->name('rekap.admin.export');
    Route::get('/rekap-statistik/print', [RekapStatistikController::class, 'printCalendar'])->name('rekap.admin.print');
});
