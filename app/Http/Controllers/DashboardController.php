<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Models\Attendance;
use App\Models\Laporan;
use App\Models\MonthlyStatistic;
use App\Models\User;
use App\Support\AdminNotificationService;
use App\Support\LokasiKantor;
use App\Support\MonthlyStatisticService;
use App\Support\WaktuAbsen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
/**
     * Validasi & normalisasi parameter pencarian (cari).
     */
    protected function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
        ]);

        return [
            'cari' => $validated['cari'] ?? null,
        ];
    }

    /**
     * Bangun query Attendance untuk Aktivitas Absensi Hari Ini.
     */
    protected function buildQuery(array $filters)
    {
        return Attendance::query()
            ->with('user')
            ->padaTanggal(now()->toDateString())
            ->cari($filters['cari'])
            ->orderBy('tgl_masuk', 'asc')
            ->orderBy('jam_masuk', 'asc');
    }

    /**
     * Ambil role dari authenticated Laravel user saja (Tahap 4.2).
     * Tidak ada fallback ke session user_id.
     */
    protected function resolveRole(Request $request): string
    {
        $user = $request->user() ?? Auth::user();
        return $user?->role ?? 'siswa';
    }

    /**
     * Ambil user_id dari authenticated Laravel user saja (Tahap 4.2).
     * Tidak ada fallback ke session user_id.
     */
    protected function resolveUserId(Request $request): ?int
    {
        $user = $request->user() ?? Auth::user();
        if ($user) {
            return (int) $user->id;
        }
        return Auth::id() !== null ? (int) Auth::id() : null;
    }

    /**
     * Hitung statistik ADMIN (global seluruh siswa, tanpa filter user_id).
     * Dipisahkan agar tidak tercampur dengan logika siswa dan mudah dipelihara.
     */
    protected function getAdminStatistics(string $today, array $filters, $baseQuery): array
    {
        // Jumlah Laporan Hari Ini — sumber data WAJIB dari tabel laporans (model Laporan),
        // bukan dari attendances. Hitung berdasarkan tanggal_laporan = hari ini (timezone Asia/Jakarta via now()).
        $totalLaporan = Laporan::whereDate('tanggal_laporan', $today)->count();

        // Statistik kehadiran hari ini - membedakan per jenis (actual DB, bukan hardcode).
        // Masuk dihitung dari status_kehadiran=Masuk agar Izin/Sakit/Dispensasi tidak ikut terhitung Masuk.
        $totalMasuk = Attendance::query()
            ->padaTanggal($today)
            ->whereNotNull('tgl_masuk')
            ->whereNotNull('jam_masuk')
            ->where(function ($q) {
                $q->where('status_kehadiran', 'Masuk')
                  ->orWhere(function ($q2) {
                      $q2->whereNull('status_kehadiran')
                         ->where('status', 'Masuk');
                  });
            })
            ->count();

        // Rekap per status kehadiran untuk dashboard (detail).
        $totalIzin = Attendance::query()
            ->padaTanggal($today)
            ->where('status_kehadiran', 'Izin')
            ->count();
        $totalDispensasi = Attendance::query()
            ->padaTanggal($today)
            ->where('status_kehadiran', 'Dispensasi')
            ->count();
        $totalSakit = Attendance::query()
            ->padaTanggal($today)
            ->where('status_kehadiran', 'Sakit')
            ->count();
        $totalTelat = Attendance::query()
            ->padaTanggal($today)
            ->where('status_kehadiran', 'Telat')
            ->count();

        // Tidak Masuk dihitung dinamis konsisten dengan Rekap: Senin-Jumat, setelah 23:00 WIB, bukan weekend/masa depan
        $totalTidakMasuk = 0;
        $todayCarbon = \Illuminate\Support\Carbon::parse($today);
        $jamSelesai = WaktuAbsen::selesai('pulang');
        $waktuSelesaiHariIni = \Illuminate\Support\Carbon::parse($today . ' ' . $jamSelesai . ':00');
        if (!$todayCarbon->isWeekend() && now()->gte($waktuSelesaiHariIni)) {
            $siswaAktifIds = User::siswaAktif()->pluck('id');
            // ATURAN BARU: Hadir valid hanya jika Masuk/Telat + jam_pulang ATAU Izin/Dispensasi/Sakit
            // Masuk tanpa Pulang TIDAK valid -> dihitung Alpha setelah 23:00
            $hadirIds = Attendance::query()
                ->padaTanggal($today)
                ->whereIn('user_id', $siswaAktifIds)
                ->where(function ($q) {
                    $q->whereIn('status_kehadiran', ['Izin', 'Dispensasi', 'Sakit'])
                        ->orWhere(function ($q2) {
                            $q2->whereIn('status_kehadiran', ['Masuk', 'Telat'])
                                ->whereNotNull('jam_pulang')->where('jam_pulang', '!=', '');
                        })
                        ->orWhere(function ($q2) {
                            $q2->whereNull('status_kehadiran')->where('status', 'Masuk')
                                ->whereNotNull('jam_pulang')->where('jam_pulang', '!=', '');
                        });
                })
                ->pluck('user_id')
                ->unique();
            // Siswa aktif yang tidak punya record VALID hari ini -> Alpha
            $totalTidakMasuk = $siswaAktifIds->diff($hadirIds)->count();
            // Jika ada record dengan status Tidak Masuk yang sudah tersimpan (legacy), hitung juga
            $totalTidakMasuk += Attendance::query()->padaTanggal($today)->where('status_kehadiran', 'Tidak Masuk')->whereNotIn('user_id', $siswaAktifIds)->count();
        }

        $totalPulang = Attendance::query()
            ->padaTanggal($today)
            ->where(function ($q) {
                $q->whereNotNull('tgl_pulang')
                    ->whereNotNull('jam_pulang');
            })
            ->count();

        // Total Siswa PKL Aktif (denominator, tidak dipengaruhi tanggal).
        $totalSiswa = User::siswaAktif()->count();

        // Total user berstatus Nonaktif (semua role, tidak dipengaruhi tanggal).
        $totalUserNonaktif = User::where('status', 'Nonaktif')->count();

        $persentaseKehadiran = $totalSiswa > 0
            ? round((($totalMasuk + $totalTelat) / $totalSiswa) * 100)
            : 0;

        return compact(
            'totalMasuk',
            'totalTelat',
            'totalTidakMasuk',
            'totalLaporan',
            'totalPulang',
            'totalSiswa',
            'persentaseKehadiran',
            'totalUserNonaktif',
            'totalIzin',
            'totalDispensasi',
            'totalSakit'
        );
    }

    /**
     * Hitung statistik SISWA (hanya milik user yang sedang login, filter by user_id).
     * DIPERBAIKI: gunakan MonthlyStatisticService agar sinkron 100% dengan Rekap Absen.
     * - Periode: bulan berjalan (startOfMonth - endOfMonth) sama persis dengan Rekap bulanan
     * - Hadir = Masuk/Telat + jam_pulang (distinct tanggal, via hitungHadirIzinSakitDistinct / kalender)
     * - Telat distinct dengan Pulang => Hadir+1 & Telat+1
     * - Izin = Izin + Dispensasi
     * - Sakit terpisah
     * - Alpha = hari kerja tanpa valid absen setelah WaktuAbsen::selesai('pulang'), termasuk Masuk tanpa Pulang lewat batas
     * - Laporan per periode bulan berjalan
     * WAJIB menggunakan $userId dari Auth/session, bukan dari $request input.
     */
    protected function getSiswaStatistics(int $userId, string $today): array
    {
        // Absen hari ini milik siswa tersebut (1 record per hari, ambil yang terbaru).
        $myAbsenMasukHariIni = Attendance::where('user_id', $userId)
            ->padaTanggal($today)
            ->orderByDesc('id')
            ->first();

        // Untuk pulang, gunakan record yang sama (cek jam_pulang di view).
        $myAbsenPulangHariIni = $myAbsenMasukHariIni;

        // === PERBAIKAN: Statistik periode bulanan via MonthlyStatisticService (sinkron Rekap) ===
        $now = now();
        $start = $now->copy()->startOfMonth()->startOfDay();
        $end = $now->copy()->endOfMonth()->endOfDay();
        $user = User::find($userId);

        $data = MonthlyStatisticService::hitungUntukUserPeriode($userId, $start, $end, $now);
        $hadir = $data['jumlah_hadir'] ?? 0;
        $izin = $data['jumlah_izin'] ?? 0;
        $sakit = $data['jumlah_sakit'] ?? 0;
        $alpha = $data['jumlah_alpha'] ?? 0;
        $telat = 0;
        $laporan = $data['jumlah_laporan'] ?? 0;
        if ($user) {
            try {
                $kal = MonthlyStatisticService::kalenderUntukUsers(collect([$user]), $start, $end, $now);
                $tot = $kal['totals'][$userId] ?? null;
                if ($tot) {
                    $hadir = $tot['hadir'];
                    $telat = $tot['telat'];
                    $izin = $tot['izin'];
                    $sakit = $tot['sakit'];
                    $alpha = $tot['alpha'];
                    $laporan = $tot['laporan'];
                } else {
                    $telat = $data['jumlah_telat'] ?? 0;
                }
            } catch (\Throwable $e) {
                $telat = $data['jumlah_telat'] ?? 0;
            }
        }

        // Dispensasi terpisah untuk kartu Dispensasi (periode bulanan, raw count periode)
        $myTotalDispensasi = 0;
        if ($user) {
            $myTotalDispensasi = Attendance::where(function ($q) use ($userId, $user) {
                $q->where('user_id', $userId)
                  ->orWhere(function ($q2) use ($user) {
                      $q2->whereNull('user_id')->where('nama', $user->nama);
                  });
            })->whereDate('tgl_masuk', '>=', $start->toDateString())
              ->whereDate('tgl_masuk', '<=', $end->toDateString())
              ->where('status_kehadiran', 'Dispensasi')
              ->count();
        }

        $myTotalKehadiran = $hadir; // Hadir (Masuk/Telat + Pulang) distinct - sinkron Rekap
        $myTotalTelat = $telat; // Telat dengan Pulang distinct
        $myTotalIzin = $izin; // Izin + Dispensasi
        $myTotalSakit = $sakit;
        $myTotalTidakMasuk = $alpha; // Alpha
        $myJumlahLaporan = $laporan; // Laporan periode bulanan sinkron Rekap

        // Tidak Masuk hari ini (dinamis, hanya setelah WaktuAbsen::selesai('pulang'))
        // Gunakan kalender cell hari ini agar logic sama persis dengan resolveCellStatus
        $myIsTidakMasukHariIni = false;
        if ($user) {
            try {
                $todayCarbon = \Illuminate\Support\Carbon::parse($today)->startOfDay();
                $todayEnd = $todayCarbon->copy()->endOfDay();
                $kalToday = MonthlyStatisticService::kalenderUntukUsers(collect([$user]), $todayCarbon, $todayEnd, $now);
                $cell = $kalToday['matrix'][$userId][$today] ?? null;
                if ($cell && $cell['code'] === 'A') {
                    $myIsTidakMasukHariIni = true;
                }
            } catch (\Throwable $e) {
                // fallback: pakai WaktuAbsen::selesai('pulang') tanpa hardcode
                $jamSelesai = WaktuAbsen::selesai('pulang');
                $waktuSelesaiHariIni = \Illuminate\Support\Carbon::parse($today . ' ' . $jamSelesai . ':00');
                $hasValidToday = $myAbsenMasukHariIni && (
                    (!empty($myAbsenMasukHariIni->jam_pulang)) ||
                    in_array($myAbsenMasukHariIni->status_kehadiran, ['Izin','Dispensasi','Sakit'], true)
                );
                $isWeekend = \Illuminate\Support\Carbon::parse($today)->isWeekend();
                if (!$isWeekend && !$hasValidToday && $now->gte($waktuSelesaiHariIni)) {
                    $hasIncompleteToday = $myAbsenMasukHariIni && in_array($myAbsenMasukHariIni->status_kehadiran, ['Masuk','Telat'], true) && empty($myAbsenMasukHariIni->jam_pulang);
                    if ($hasIncompleteToday) {
                        $myIsTidakMasukHariIni = true;
                    } elseif (!$myAbsenMasukHariIni) {
                        $myIsTidakMasukHariIni = true;
                    }
                }
            }
        }

        return compact(
            'myAbsenMasukHariIni',
            'myAbsenPulangHariIni',
            'myTotalKehadiran',
            'myTotalTelat',
            'myIsTidakMasukHariIni',
            'myTotalTidakMasuk',
            'myTotalIzin',
            'myTotalDispensasi',
            'myTotalSakit',
            'myJumlahLaporan'
        );
    }

    /**
     * Tampilkan dashboard dengan statistik & tabel aktivitas absensi hari ini.
     * Seluruh data dihitung dari database dan mengikuti pencarian yang aktif.
     *
     * - ADMIN: statistik global seluruh siswa (tanpa filter user_id)
     * - SISWA: statistik privat hanya milik user yang login (where user_id = Auth::id())
     */
    public function index(Request $request)
    {
        // Username: hanya dari authenticated Laravel user (Tahap 4.2).
        $userAuth = $request->user() ?? Auth::user();
        $username = $userAuth?->username ?? $userAuth?->nama ?? null;

        $role   = $this->resolveRole($request);
        $userId = $this->resolveUserId($request);
        $today  = now()->toDateString();

        $filters = $this->validatedFilters($request);

        // Default nilai admin (agar view tidak undefined saat role siswa).
        $totalMasuk = 0;
        $totalTelat = 0;
        $totalTidakMasuk = 0;
        $totalLaporan = 0;
        $totalPulang = 0;
        $totalSiswa = 0;
        $totalUserNonaktif = 0;
        $persentaseKehadiran = 0;
        $totalIzin = 0;
        $totalDispensasi = 0;
        $totalSakit = 0;

        // Default nilai siswa (agar view tidak undefined saat role admin).
        $myAbsenMasukHariIni = null;
        $myAbsenPulangHariIni = null;
        $myTotalKehadiran = 0;
        $myTotalTelat = 0;
        $myTotalTidakMasuk = 0;
        $myTotalIzin = 0;
        $myTotalDispensasi = 0;
        $myTotalSakit = 0;
        $myJumlahLaporan = 0;
        $myIsTidakMasukHariIni = false;

        // Aktivitas tabel hanya untuk admin - siswa tidak memerlukan query sama sekali.
        $activities = null;

        if ($role === 'admin') {
            // Sinkron Alpha existing — hari ini + tanggal sebelumnya yang belum ada (anti-duplikat, tetap cek batas 23:00)
            try {
                AdminNotificationService::syncAlphaRecent(7);
            } catch (\Throwable $e) {
                // diamkan
            }
            // ADMIN: query dasar sesuai pencarian (dibatasi ke hari ini) - hanya dibuat untuk admin.
            $baseQuery = $this->buildQuery($filters);

            // ADMIN: statistik global seluruh siswa
            $adminStats = $this->getAdminStatistics($today, $filters, $baseQuery);
            extract($adminStats);

            // ADMIN: aktivitas seluruh siswa hari ini (dengan search & pagination)
            $activities = (clone $baseQuery)->paginate(10)->withQueryString();
        } else {
            // SISWA: statistik privat hanya milik siswa tersebut
            // Tidak ada query aktivitas tabel untuk siswa (hemat resource + keamanan).
            if ($userId) {
                $siswaStats = $this->getSiswaStatistics($userId, $today);
                extract($siswaStats);
            }
            // $activities tetap null untuk siswa - view akan menyembunyikan tabel sepenuhnya.
        }

        return view('dashboard', compact(
            'username',
            'role',
            'filters',
            'activities',
            // Admin stats
            'totalMasuk',
            'totalTelat',
            'totalTidakMasuk',
            'totalLaporan',
            'totalPulang',
            'totalSiswa',
            'persentaseKehadiran',
            'totalUserNonaktif',
            'totalIzin',
            'totalDispensasi',
            'totalSakit',
            // Siswa stats (privat)
            'myAbsenMasukHariIni',
            'myAbsenPulangHariIni',
            'myTotalKehadiran',
            'myTotalTelat',
            'myTotalTidakMasuk',
            'myIsTidakMasukHariIni',
            'myTotalIzin',
            'myTotalDispensasi',
            'myTotalSakit',
            'myJumlahLaporan'
        ));
    }

    /**
     * Export Excel (.xlsx) sesuai filter yang sedang aktif.
     * Admin: seluruh data. Siswa: hanya miliknya.
     */
    public function exportExcel(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $query   = $this->buildQuery($filters);

        $role   = $this->resolveRole($request);
        $userId = $this->resolveUserId($request);
        if ($role !== 'admin' && $userId) {
            $query->where('user_id', $userId);
        } elseif ($role !== 'admin' && !$userId) {
            $query->whereRaw('1 = 0');
        }

        $activities = $query->get();

        $filename = 'laporan-absensi-'.date('Ymd-His').'.xlsx';

        return Excel::download(new AttendanceExport($activities), $filename);
    }

    /**
     * Halaman cetak bersih (hanya tabel) yang otomatis memanggil window.print().
     * Untuk siswa, hanya menampilkan data miliknya.
     */
    public function print(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $query   = $this->buildQuery($filters);

        $role   = $this->resolveRole($request);
        $userId = $this->resolveUserId($request);
        if ($role !== 'admin' && $userId) {
            $query->where('user_id', $userId);
        } elseif ($role !== 'admin' && !$userId) {
            $query->whereRaw('1 = 0');
        }

        $activities = $query->get();
        $lokasi = LokasiKantor::all();

        return view('print', [
            'activities' => $activities,
            'filter'     => $filters,
            'lokasi'     => $lokasi,
        ]);
    }
}
