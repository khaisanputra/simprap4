<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Laporan;
use App\Models\MonthlyStatistic;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Layanan snapshot statistik bulanan per siswa.
 * - Tidak menghapus data attendances asli
 * - Statistik bulan berjalan dihitung live dari attendances (filter tgl_masuk bulan+tahun ini)
 * - Arsip bulan sebelumnya disimpan sebagai snapshot di monthly_statistics (upsert per user+bulan+tahun)
 * - Dipanggil secara lazy: ketika dashboard siswa/admin dibuka, otomatis sinkron arsip bulan lalu jika belum ada
 */
class MonthlyStatisticService
{
    /**
     * Hitung statistik bulanan untuk satu user pada bulan+tahun tertentu.
     * Konsep baru sederhana (Rekap Bulanan):
     * - Hadir : Masuk/Telat + jam_pulang not null (harus pulang)
     * - Izin  : Izin + Dispensasi digabung
     * - Sakit : Sakit
     * - Alpha : hari aktif sistem tanpa absen (setelah jam pulang dibuka) tanpa Izin/Sakit/Dispensasi
     * - Laporan : hitung laporans per user per bulan+tahun
     * Field lama tetap dihitung untuk kompatibilitas (Dashboard & arsip lama) namun tidak ditampilkan di Rekap baru.
     */
    public static function hitungUntukUserPerBulan(int $userId, int $bulan, int $tahun, ?Carbon $now = null): array
    {
        $now = $now ? Carbon::parse($now) : now();
        $user = User::find($userId);
        if (!$user) {
            return self::kosong($bulan, $tahun);
        }

        // Filter attendances pada bulan+tahun tersebut (berdasarkan tgl_masuk)
        $base = Attendance::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere(function ($q2) use ($user) {
                  $q2->whereNull('user_id')->where('nama', $user->nama);
              });
        })->whereYear('tgl_masuk', $tahun)->whereMonth('tgl_masuk', $bulan);

        // === PERHITUNGAN BARU (1 siswa + 1 tanggal = 1 status, distinct tanggal) ===
        $bulanStart = Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
        $bulanEnd = $bulanStart->copy()->endOfMonth()->endOfDay();
        $statusCounts = self::hitungHadirIzinSakitDistinct($user, $bulanStart, $bulanEnd);
        $jumlahHadir = $statusCounts['hadir'];
        $jumlahIzin = $statusCounts['izin'];
        $jumlahSakit = $statusCounts['sakit'];

        // Alpha: hari aktif sistem tanpa absen setelah jam pulang dibuka
        $jumlahAlpha = self::hitungAlphaBulanan($user, $bulan, $tahun, $now);

        // Laporan: hitung dari tabel laporans
        $jumlahLaporan = Laporan::where('user_id', $user->id)
            ->whereYear('tanggal_laporan', $tahun)
            ->whereMonth('tanggal_laporan', $bulan)
            ->count();

        // === PERHITUNGAN LAMA (untuk kompatibilitas Dashboard & arsip lama) ===
        $jumlahMasuk = (clone $base)->where(function ($q) {
            $q->where('status_kehadiran', 'Masuk')
              ->orWhere(function ($q2) {
                  $q2->whereNull('status_kehadiran')->where('status', 'Masuk');
              });
        })->count();
        $jumlahTelat = (clone $base)->where('status_kehadiran', 'Telat')->count();
        $jumlahIzinOnly = (clone $base)->where('status_kehadiran', 'Izin')->count();
        $jumlahSakitOnly = $jumlahSakit;
        $jumlahDispensasi = (clone $base)->where('status_kehadiran', 'Dispensasi')->count();
        $jumlahPulang = (clone $base)->whereNotNull('jam_pulang')->whereNotNull('tgl_pulang')->count();
        $jumlahPulangAlt = (clone $base)->whereNotNull('jam_pulang')->whereNull('tgl_pulang')->count();
        $jumlahPulang += $jumlahPulangAlt;
        $jumlahTidakMasuk = self::hitungTidakMasukBulanan($user, $bulan, $tahun, $now);
        $distinctHariKerja = Attendance::whereNotNull('tgl_masuk')->whereNotNull('jam_masuk')
            ->whereYear('tgl_masuk', $tahun)->whereMonth('tgl_masuk', $bulan)
            ->selectRaw('DATE(tgl_masuk) as d')->distinct()->pluck('d')->count();
        if ($distinctHariKerja > 0) {
            $persentase = (int) round((($jumlahMasuk + $jumlahTelat) / $distinctHariKerja) * 100);
            $persentase = max(0, min(100, $persentase));
        } else {
            $persentase = 0;
        }

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            // field lama
            'jumlah_masuk' => $jumlahMasuk,
            'jumlah_pulang' => $jumlahPulang,
            'jumlah_izin' => $jumlahIzin, // untuk rekap baru, Izin sudah gabungan (overwrite lama); dashboard lama akan melihat gabungan
            'jumlah_sakit' => $jumlahSakitOnly,
            'jumlah_dispensasi' => $jumlahDispensasi,
            'jumlah_telat' => $jumlahTelat,
            'jumlah_tidak_masuk' => $jumlahTidakMasuk,
            'persentase_kehadiran' => $persentase,
            // field baru
            'jumlah_hadir' => $jumlahHadir,
            'jumlah_alpha' => $jumlahAlpha,
            'jumlah_laporan' => $jumlahLaporan,
        ];
    }

    protected static function kosong(int $bulan, int $tahun): array
    {
        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'jumlah_masuk' => 0,
            'jumlah_pulang' => 0,
            'jumlah_izin' => 0,
            'jumlah_sakit' => 0,
            'jumlah_dispensasi' => 0,
            'jumlah_telat' => 0,
            'jumlah_tidak_masuk' => 0,
            'persentase_kehadiran' => 0,
            'jumlah_hadir' => 0,
            'jumlah_alpha' => 0,
            'jumlah_laporan' => 0,
        ];
    }

    protected static function kosongPeriode(Carbon $start, Carbon $end): array
    {
        return [
            'bulan' => (int) $start->format('n'),
            'tahun' => (int) $start->format('Y'),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'jumlah_masuk' => 0,
            'jumlah_pulang' => 0,
            'jumlah_izin' => 0,
            'jumlah_sakit' => 0,
            'jumlah_dispensasi' => 0,
            'jumlah_telat' => 0,
            'jumlah_tidak_masuk' => 0,
            'persentase_kehadiran' => 0,
            'jumlah_hadir' => 0,
            'jumlah_alpha' => 0,
            'jumlah_laporan' => 0,
        ];
    }

    /**
     * Hitung statistik untuk rentang tanggal arbitrary (Mingguan/Periode/Bulanan via wrapper).
     * Menggunakan start_date & end_date sebagai dasar query.
     */
    public static function hitungUntukUserPeriode(int $userId, Carbon $start, Carbon $end, ?Carbon $now = null): array
    {
        $now = $now ? Carbon::parse($now) : now();
        $user = User::find($userId);
        if (!$user) {
            return self::kosongPeriode($start, $end);
        }

        $startStr = $start->toDateString();
        $endStr = $end->toDateString();

        $base = Attendance::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere(function ($q2) use ($user) {
                  $q2->whereNull('user_id')->where('nama', $user->nama);
              });
        })->whereDate('tgl_masuk', '>=', $startStr)->whereDate('tgl_masuk', '<=', $endStr);

        $statusCounts = self::hitungHadirIzinSakitDistinct($user, $start, $end);
        $jumlahHadir = $statusCounts['hadir'];
        $jumlahIzin = $statusCounts['izin'];
        $jumlahSakit = $statusCounts['sakit'];
        $jumlahAlpha = self::hitungAlphaPeriode($user, $start, $end, $now);
        $jumlahLaporan = Laporan::where('user_id', $user->id)
            ->whereDate('tanggal_laporan', '>=', $startStr)
            ->whereDate('tanggal_laporan', '<=', $endStr)
            ->count();

        // Lama untuk kompatibilitas
        $jumlahMasuk = (clone $base)->where(function ($q) {
            $q->where('status_kehadiran', 'Masuk')
              ->orWhere(fn($q2) => $q2->whereNull('status_kehadiran')->where('status', 'Masuk'));
        })->count();
        $jumlahTelat = (clone $base)->where('status_kehadiran', 'Telat')->count();
        $jumlahDispensasi = (clone $base)->where('status_kehadiran', 'Dispensasi')->count();
        $jumlahPulang = (clone $base)->whereNotNull('jam_pulang')->whereNotNull('tgl_pulang')->count();
        $jumlahPulang += (clone $base)->whereNotNull('jam_pulang')->whereNull('tgl_pulang')->count();
        $jumlahTidakMasuk = $jumlahAlpha; // Alpha = Tidak Masuk baru
        $distinctHariKerja = Attendance::whereNotNull('tgl_masuk')->whereNotNull('jam_masuk')
            ->whereDate('tgl_masuk', '>=', $startStr)->whereDate('tgl_masuk', '<=', $endStr)
            ->selectRaw('DATE(tgl_masuk) as d')->distinct()->pluck('d')->count();
        $persentase = 0;
        if ($distinctHariKerja > 0) {
            $persentase = (int) round((($jumlahMasuk + $jumlahTelat) / $distinctHariKerja) * 100);
            $persentase = max(0, min(100, $persentase));
        }

        return [
            'bulan' => (int) $start->format('n'),
            'tahun' => (int) $start->format('Y'),
            'start_date' => $startStr,
            'end_date' => $endStr,
            'jumlah_masuk' => $jumlahMasuk,
            'jumlah_pulang' => $jumlahPulang,
            'jumlah_izin' => $jumlahIzin,
            'jumlah_sakit' => $jumlahSakit,
            'jumlah_dispensasi' => $jumlahDispensasi,
            'jumlah_telat' => $jumlahTelat,
            'jumlah_tidak_masuk' => $jumlahTidakMasuk,
            'persentase_kehadiran' => $persentase,
            'jumlah_hadir' => $jumlahHadir,
            'jumlah_alpha' => $jumlahAlpha,
            'jumlah_laporan' => $jumlahLaporan,
        ];
    }

    /**
     * Hitung Hadir/Izin/Sakit dengan konsep 1 siswa + 1 tanggal = 1 status (distinct tanggal, prioritas Hadir > Izin > Sakit).
     */
    protected static function hitungHadirIzinSakitDistinct(User $user, Carbon $start, Carbon $end): array
    {
        $startStr = $start->toDateString();
        $endStr = $end->toDateString();

        $records = Attendance::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere(function ($q2) use ($user) {
                  $q2->whereNull('user_id')->where('nama', $user->nama);
              });
        })->whereDate('tgl_masuk', '>=', $startStr)->whereDate('tgl_masuk', '<=', $endStr)
          ->whereNotNull('tgl_masuk')->whereNotNull('jam_masuk')
          ->get(['tgl_masuk','status_kehadiran','status','jam_pulang']);

        $grouped = $records->groupBy(function ($r) {
            return Carbon::parse($r->tgl_masuk)->toDateString();
        });

        $hadir = 0; $izin = 0; $sakit = 0;
        foreach ($grouped as $date => $rows) {
            $hasHadir = $rows->contains(function ($r) {
                $isMasukTelat = in_array($r->status_kehadiran, ['Masuk', 'Telat'], true);
                $isLegacyMasuk = $r->status_kehadiran === null && $r->status === 'Masuk';
                $hasPulang = $r->jam_pulang !== null && $r->jam_pulang !== '';
                return ($isMasukTelat || $isLegacyMasuk) && $hasPulang;
            });
            $hasIzin = $rows->contains(function ($r) {
                return in_array($r->status_kehadiran, ['Izin', 'Dispensasi'], true);
            });
            $hasSakit = $rows->contains(function ($r) {
                return $r->status_kehadiran === 'Sakit';
            });

            if ($hasHadir) {
                $hadir++;
            } elseif ($hasIzin) {
                $izin++;
            } elseif ($hasSakit) {
                $sakit++;
            }
            // Masuk tanpa Pulang sebelum batas tidak dihitung (setelah batas dihitung Alpha, jadi tidak masuk hadir di sini)
        }

        return ['hadir'=>$hadir,'izin'=>$izin,'sakit'=>$sakit];
    }

    /**
     * Hitung Alpha untuk rentang arbitrary (dipakai Mingguan/Periode/Bulanan).
     * FIX FINAL: Hapus ketergantungan pada distinct attendance siswa lain.
     * Sumber hari aktif: karena project tidak memiliki tabel hari_kerja/kalender/jadwal khusus
     * (audit: settings hanya lokasi & jam, config/absensi hanya jam, tidak ada tabel hari kerja),
     * gunakan rentang kalender [start, end] sebagai hari aktif (paling netral, tidak mengasumsikan Senin-Jumat/libur).
     * Tetap hormati: sebelum user dibuat skip, masa depan skip, hari ini sebelum WaktuAbsen::selesai('pulang') skip,
     * dan hanya hitung jika siswa tidak punya status valid (Hadir dengan Pulang / Izin / Sakit) pada tanggal tersebut.
     * PERUBAHAN BARU: Masuk tanpa Pulang TIDAK lagi membatalkan Alpha; setelah batas selesai, Masuk tanpa Pulang = Alpha.
     */
    public static function hitungAlphaPeriode(User $user, Carbon $start, Carbon $end, Carbon $now): int
    {
        $todayStr = $now->toDateString();
        $startStr = $start->toDateString();
        $endStr = $end->toDateString();

        $userCreated = $user->created_at ? Carbon::parse($user->created_at)->toDateString() : $startStr;
        $jamPulangSelesai = WaktuAbsen::selesai('pulang');

        // Ambil tanggal dimana user mempunyai status VALID (Hadir dengan Pulang / Izin / Dispensasi / Sakit)
        // Incomplete (Masuk/Telat tanpa jam_pulang) tidak dianggap valid sehingga tetap dihitung Alpha setelah batas selesai.
        $records = Attendance::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere(fn($q2) => $q2->whereNull('user_id')->where('nama', $user->nama));
        })->whereNotNull('tgl_masuk')->whereNotNull('jam_masuk')
          ->whereDate('tgl_masuk', '>=', $startStr)->whereDate('tgl_masuk', '<=', $endStr)
          ->get(['tgl_masuk','status_kehadiran','status','jam_pulang']);

        $grouped = $records->groupBy(fn($r) => Carbon::parse($r->tgl_masuk)->toDateString());
        $validDatesSet = [];
        foreach ($grouped as $d => $rows) {
            // ATURAN BARU 23:00 + HADIR HANYA DENGAN PULANG:
            // Valid hanya jika: Hadir (Masuk/Telat + jam_pulang) ATAU Izin/Dispensasi/Sakit.
            // Masuk/Telat TANPA jam_pulang BUKAN valid -> setelah 23:00 menjadi Alpha, sebelum 23:00 pending.
            $hasHadir = $rows->contains(function ($r) {
                $isMasukTelat = in_array($r->status_kehadiran, ['Masuk', 'Telat'], true);
                $isLegacyMasuk = $r->status_kehadiran === null && $r->status === 'Masuk';
                $hasPulang = !empty($r->jam_pulang);
                return ($isMasukTelat || $isLegacyMasuk) && $hasPulang;
            });
            $hasIzin = $rows->contains(fn($r) => in_array($r->status_kehadiran, ['Izin', 'Dispensasi'], true));
            $hasSakit = $rows->contains(fn($r) => $r->status_kehadiran === 'Sakit');
            if ($hasHadir || $hasIzin || $hasSakit) {
                $validDatesSet[$d] = true;
            }
        }

        $count = 0;
        $cursor = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($cursor->lte($endDay)) {
            $d = $cursor->toDateString();
            // HARI AKTIF: hanya Senin-Jumat, Sabtu-Minggu SKIP (sesuai aturan final PKL)
            if ($cursor->isWeekend()) {
                $cursor->addDay();
                continue;
            }
            // Lewati hari sebelum user dibuat
            if ($d < $userCreated) {
                $cursor->addDay();
                continue;
            }
            // Jangan hitung hari di masa depan
            if ($d > $todayStr) {
                $cursor->addDay();
                continue;
            }
            // Jika hari ini dan belum lewat jam pulang selesai, belum eligible (Alpha tidak boleh sebelum batas akhir)
            if ($d === $todayStr) {
                $waktuSelesaiHariIni = Carbon::parse($todayStr . ' ' . $jamPulangSelesai . ':00');
                if ($now->lt($waktuSelesaiHariIni)) {
                    $cursor->addDay();
                    continue;
                }
            }
            // Jika user sudah mempunyai status valid hari itu, bukan Alpha
            if (isset($validDatesSet[$d])) {
                $cursor->addDay();
                continue;
            }
            $count++;
            $cursor->addDay();
        }

        return $count;
    }

    /**
     * Hitung Tidak Masuk khusus bulan+tahun untuk user.
     * Logika legacy dipertahankan untuk kompatibilitas, namun diselaraskan dengan aturan Alpha baru:
     * - Gunakan jam_pulang_selesai sebagai batas (bukan mulai)
     * - Masuk tanpa Pulang setelah batas dianggap Alpha (tidak membatalkan Alpha)
     * Untuk konsistensi, delegasikan ke hitungAlphaBulanan yang sudah menggunakan kalender Mon-Fri.
     */
    public static function hitungTidakMasukBulanan(User $user, int $bulan, int $tahun, Carbon $now): int
    {
        return self::hitungAlphaBulanan($user, $bulan, $tahun, $now);
    }

    /**
     * Hitung Alpha bulanan — sama dengan Tidak Masuk namun dengan konsep baru:
     * Alpha hanya setelah jam pulang dibuka, tanpa absen Izin/Sakit/Dispensasi.
     * Delegasi ke hitungAlphaPeriode dengan rentang bulan.
     */
    public static function hitungAlphaBulanan(User $user, int $bulan, int $tahun, Carbon $now): int
    {
        $start = Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();
        return self::hitungAlphaPeriode($user, $start, $end, $now);
    }

    /**
     * Pastikan arsip bulan sebelumnya tersedia (lazy auto-archive).
     * Dipanggil setiap kali dashboard dibuka: menyinkronkan bulan-bulan yang sudah lewat tapi belum ada arsip.
     * Aman: tidak menghapus data, hanya upsert snapshot.
     */
    public static function ensureArchivedForUser(int $userId, ?Carbon $now = null): void
    {
        $now = $now ? Carbon::parse($now) : now();
        $user = User::find($userId);
        if (!$user) return;

        // Tentukan batas: bulan berjalan tidak diarsipkan (live), hanya bulan < current
        $currentBulan = (int)$now->format('n');
        $currentTahun = (int)$now->format('Y');

        // Cari semua bulan+tahun yang memiliki aktivitas sistem sebelum bulan berjalan
        // Ambil distinct bulan+tahun dari attendances (year, month)
        $periods = Attendance::whereNotNull('tgl_masuk')->whereNotNull('jam_masuk')
            ->selectRaw('YEAR(tgl_masuk) as tahun, MONTH(tgl_masuk) as bulan')
            ->distinct()->get()
            ->map(fn($r) => ['bulan'=>(int)$r->bulan, 'tahun'=>(int)$r->tahun])
            ->filter(function($p) use ($currentBulan, $currentTahun) {
                // Hanya bulan yang sudah lewat (strictly before current)
                if ($p['tahun'] < $currentTahun) return true;
                if ($p['tahun'] === $currentTahun && $p['bulan'] < $currentBulan) return true;
                return false;
            })->values();

        // Untuk efisiensi, hanya proses bulan yang belum ada arsip untuk user ini,
        // namun juga backfill arsip lama yang belum memiliki field baru (jumlah_hadir/alpha/laporan)
        $existingRows = MonthlyStatistic::untukUser($userId)->get();
        $existingMap = $existingRows->keyBy(fn($m)=> $m->tahun.'-'.$m->bulan);

        foreach ($periods as $p) {
            $key = $p['tahun'].'-'.$p['bulan'];
            $existing = $existingMap->get($key);
            if ($existing) {
                // Backfill: jika arsip lama belum memiliki field baru (atau masih 0 tapi seharusnya ada), update
                $needsBackfill = false;
                // Cek apakah field baru belum terisi (null) atau masih default 0 padahal ada aktivitas
                if ($existing->getAttribute('jumlah_hadir') === null || $existing->getAttribute('jumlah_alpha') === null || $existing->getAttribute('jumlah_laporan') === null) {
                    $needsBackfill = true;
                } elseif (($existing->jumlah_hadir == 0 && $existing->jumlah_alpha == 0 && $existing->jumlah_laporan == 0)
                    && ($existing->jumlah_masuk > 0 || $existing->jumlah_tidak_masuk > 0 || $existing->jumlah_izin > 0)) {
                    // Kemungkinan arsip lama sebelum migrasi baru
                    $needsBackfill = true;
                }
                if (!$needsBackfill) continue;
            }
            // Hitung dan simpan (buat baru atau backfill)
            $data = self::hitungUntukUserPerBulan($userId, $p['bulan'], $p['tahun'], $now);
            MonthlyStatistic::updateOrCreate(
                ['user_id'=>$userId, 'bulan'=>$p['bulan'], 'tahun'=>$p['tahun']],
                $data
            );
        }
    }

    /**
     * Pastikan arsip untuk semua siswa (dipanggil admin view, dibatasi agar tidak berat: hanya sinkron user yang ditampilkan atau semua jika perlu)
     */
    public static function ensureArchivedForAll(?Carbon $now = null): void
    {
        $now = $now ? Carbon::parse($now) : now();
        $siswaIds = User::siswaAktif()->pluck('id')->toArray();
        foreach ($siswaIds as $uid) {
            try {
                self::ensureArchivedForUser($uid, $now);
            } catch (\Throwable $e) {
                // jangan stop batch
            }
        }
    }

    /**
     * Refresh atau buat snapshot untuk bulan+lalu (dipanggil eksplisit jika diperlukan, misal command)
     */
    public static function snapshotBulanan(int $userId, int $bulan, int $tahun, ?Carbon $now = null): MonthlyStatistic
    {
        $data = self::hitungUntukUserPerBulan($userId, $bulan, $tahun, $now);
        return MonthlyStatistic::updateOrCreate(
            ['user_id'=>$userId, 'bulan'=>$bulan, 'tahun'=>$tahun],
            $data
        );
    }

    /**
     * Ambil statistik bulan berjalan live (tidak dari tabel arsip, dihitung real-time dari attendances).
     * Untuk dashboard siswa bulan ini.
     */
    public static function getStatistikBulanBerjalan(int $userId, ?Carbon $now = null): array
    {
        $now = $now ? Carbon::parse($now) : now();
        $bulan = (int)$now->format('n');
        $tahun = (int)$now->format('Y');
        return self::hitungUntukUserPerBulan($userId, $bulan, $tahun, $now);
    }

    /**
     * Ambil arsip bulan lalu untuk user (ordered terbaru)
     */
    public static function getArsipUntukUser(int $userId, int $limit = 6)
    {
        return MonthlyStatistic::untukUser($userId)->terbaru()->limit($limit)->get();
    }

    // =========================================================
    // KALENDER REKAP: tanggal dinamis + status per sel
    // =========================================================

    /**
     * Bangun daftar tanggal inclusive dari start sampai end.
     */
    public static function tanggalList(Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        $list = collect();
        $cursor = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();
        while ($cursor->lte($endDay)) {
            $list->push($cursor->copy());
            $cursor->addDay();
        }
        return $list;
    }

    /**
     * Resolve status satu sel tanggal untuk satu siswa.
     * Mengikuti aturan Alpha yang sama dengan hitungAlphaPeriode.
     * ATURAN BARU 23:00 + HADIR HANYA DENGAN PULANG:
     * - Masuk + Pulang tepat waktu => H (Hadir)
     * - Masuk terlambat + Pulang => T (Telat)
     * - Masuk tanpa Pulang sebelum 23:00 => pending • (sementara Hadir/Telat, tidak dihitung Alpha/Hadir)
     * - Masuk tanpa Pulang setelah 23:00 / hari lampau tanpa Pulang => A (Alpha)
     * - Tidak ada record setelah 23:00 => A (Alpha)
     * - Izin/Dispensasi/Sakit tetap I/S (tidak pernah Alpha)
     */
    protected static function resolveCellStatus(User $user, Carbon $date, \Illuminate\Support\Collection $records, Carbon $now, string $todayStr, string $userCreatedStr, string $jamPulangSelesai): array
    {
        $dateStr = $date->toDateString();
        $isWeekend = $date->isWeekend();
        $isFuture = $dateStr > $todayStr;
        $isBeforeCreated = $dateStr < $userCreatedStr;

        if ($isWeekend) {
            return [
                'code' => '-',
                'type' => 'weekend',
                'label' => '-',
                'color' => '#64748B',
                'bg' => '#F1F5F9',
                'border' => '#E2E8F0',
                'isClickable' => false,
                'records' => $records,
                'attendance' => null,
                'isWeekend' => true,
                'isFuture' => false,
                'note' => 'Akhir pekan',
            ];
        }
        if ($isFuture) {
            return [
                'code' => '-',
                'type' => 'future',
                'label' => '-',
                'color' => '#9CA3AF',
                'bg' => '#F8FAFC',
                'border' => '#E5E7EB',
                'isClickable' => false,
                'records' => $records,
                'attendance' => null,
                'isWeekend' => false,
                'isFuture' => true,
                'note' => 'Masa depan',
            ];
        }
        if ($isBeforeCreated) {
            return [
                'code' => '-',
                'type' => 'before',
                'label' => '-',
                'color' => '#9CA3AF',
                'bg' => '#F8FAFC',
                'border' => '#E5E7EB',
                'isClickable' => false,
                'records' => $records,
                'attendance' => null,
                'note' => 'Sebelum bergabung',
            ];
        }

        if ($records->isNotEmpty()) {
            $hasTelatWithPulang = $records->contains(function ($r) {
                $isTelat = $r->status_kehadiran === 'Telat';
                $hasPulang = !empty($r->jam_pulang);
                return $isTelat && $hasPulang;
            });
            $hasHadirWithPulang = $records->contains(function ($r) {
                $isMasuk = $r->status_kehadiran === 'Masuk' || ($r->status_kehadiran === null && $r->status === 'Masuk');
                $hasPulang = !empty($r->jam_pulang);
                return $isMasuk && $hasPulang;
            });
            $hasIzin = $records->contains(fn($r) => in_array($r->status_kehadiran, ['Izin', 'Dispensasi'], true));
            $hasSakit = $records->contains(fn($r) => $r->status_kehadiran === 'Sakit');
            $hasMasukOrTelatIncomplete = $records->contains(function ($r) {
                $isMasukTelat = in_array($r->status_kehadiran, ['Masuk', 'Telat'], true) || ($r->status_kehadiran === null && $r->status === 'Masuk');
                $hasPulang = !empty($r->jam_pulang);
                return $isMasukTelat && !$hasPulang;
            });

            if ($hasTelatWithPulang) {
                $att = $records->first(fn($r) => $r->status_kehadiran === 'Telat' && !empty($r->jam_pulang));
                return [
                    'code' => 'T',
                    'type' => 'telat',
                    'label' => 'T',
                    'color' => '#92400E',
                    'bg' => '#FEF3C7',
                    'border' => '#FDE68A',
                    'isClickable' => true,
                    'records' => $records,
                    'attendance' => $att,
                ];
            }
            if ($hasHadirWithPulang) {
                $att = $records->first(fn($r) => ($r->status_kehadiran === 'Masuk' || ($r->status_kehadiran === null && $r->status === 'Masuk')) && !empty($r->jam_pulang));
                return [
                    'code' => 'H',
                    'type' => 'hadir',
                    'label' => 'H',
                    'color' => '#15803D',
                    'bg' => '#DCFCE7',
                    'border' => '#BBF7D0',
                    'isClickable' => true,
                    'records' => $records,
                    'attendance' => $att,
                ];
            }
            if ($hasIzin) {
                $att = $records->first(fn($r) => in_array($r->status_kehadiran, ['Izin', 'Dispensasi'], true));
                return [
                    'code' => 'I',
                    'type' => 'izin',
                    'label' => 'I',
                    'color' => '#92400E',
                    'bg' => '#FFFBEB',
                    'border' => '#FDE68A',
                    'isClickable' => true,
                    'records' => $records,
                    'attendance' => $att,
                ];
            }
            if ($hasSakit) {
                $att = $records->first(fn($r) => $r->status_kehadiran === 'Sakit');
                return [
                    'code' => 'S',
                    'type' => 'sakit',
                    'label' => 'S',
                    'color' => '#1E40AF',
                    'bg' => '#DBEAFE',
                    'border' => '#BFDBFE',
                    'isClickable' => true,
                    'records' => $records,
                    'attendance' => $att,
                ];
            }
            if ($hasMasukOrTelatIncomplete) {
                // ATURAN BARU 23:00 FINALISASI:
                // - Sebelum 23:00 WIB pada hari yang sama: status sementara tetap Masuk/Telat (pending, tidak Alpha, tidak Hadir)
                // - Setelah 23:00 WIB: final menjadi Alpha (tidak pulang). Hari lampau langsung Alpha.
                $att = $records->first();
                $statusSementara = $att->status_kehadiran ?? $att->status ?? 'Masuk';
                // Normalisasi: jika legacy null treat as Masuk
                if (!in_array($statusSementara, ['Masuk','Telat'], true)) $statusSementara = 'Masuk';
                $isMasukTepatWaktu = $statusSementara === 'Masuk';
                $noteSementara = $isMasukTepatWaktu ? 'Belum Pulang (sementara Hadir)' : 'Belum Pulang (sementara Telat)';

                // Tanggal lampau: deadline sudah lewat -> Alpha
                if ($dateStr < $todayStr) {
                    return [
                        'code' => 'A',
                        'type' => 'alpha',
                        'label' => 'A',
                        'color' => '#991B1B',
                        'bg' => '#FEE2E2',
                        'border' => '#FECACA',
                        'isClickable' => true,
                        'records' => $records,
                        'attendance' => $att,
                        'note' => 'Alpha - tidak melakukan Absen Pulang (batas 23:00 terlewati)',
                    ];
                }
                if ($dateStr === $todayStr) {
                    $waktuSelesaiHariIni = Carbon::parse($todayStr . ' ' . $jamPulangSelesai . ':00');
                    if ($now->lt($waktuSelesaiHariIni)) {
                        // Sebelum 23:00 -> pending, tidak Alpha, tidak Hadir
                        return [
                            'code' => '•',
                            'type' => 'belum_pulang',
                            'label' => '•',
                            'color' => '#B45309',
                            'bg' => '#FFFBEB',
                            'border' => '#FDE68A',
                            'isClickable' => true,
                            'records' => $records,
                            'attendance' => $att,
                            'note' => $noteSementara,
                        ];
                    } else {
                        // Setelah 23:00 -> Alpha
                        return [
                            'code' => 'A',
                            'type' => 'alpha',
                            'label' => 'A',
                            'color' => '#991B1B',
                            'bg' => '#FEE2E2',
                            'border' => '#FECACA',
                            'isClickable' => true,
                            'records' => $records,
                            'attendance' => $att,
                            'note' => 'Alpha - tidak melakukan Absen Pulang (batas 23:00 terlewati)',
                        ];
                    }
                }
                // Fallback future (seharusnya sudah tertangani di atas) tetap pending
                return [
                    'code' => '•',
                    'type' => 'belum_pulang',
                    'label' => '•',
                    'color' => '#B45309',
                    'bg' => '#FFFBEB',
                    'border' => '#FDE68A',
                    'isClickable' => true,
                    'records' => $records,
                    'attendance' => $att,
                    'note' => $noteSementara,
                ];
            }
            // fallback: ada record tapi tidak match kategori (misal Tidak Masuk legacy)
            $att = $records->first();
            return [
                'code' => '-',
                'type' => 'empty',
                'label' => '-',
                'color' => '#64748B',
                'bg' => '#F1F5F9',
                'border' => '#E2E8F0',
                'isClickable' => true,
                'records' => $records,
                'attendance' => $att,
            ];
        }

        // Tidak ada record - hari kerja tanpa absen
        if ($dateStr === $todayStr) {
            $waktuSelesaiHariIni = Carbon::parse($todayStr . ' ' . $jamPulangSelesai . ':00');
            if ($now->lt($waktuSelesaiHariIni)) {
                return [
                    'code' => '-',
                    'type' => 'pending',
                    'label' => '-',
                    'color' => '#9CA3AF',
                    'bg' => '#F8FAFC',
                    'border' => '#E5E7EB',
                    'isClickable' => false,
                    'records' => $records,
                    'attendance' => null,
                    'note' => 'Belum lewat jam pulang',
                ];
            }
        }
        // Hari kerja tanpa absen = Alpha
        return [
            'code' => 'A',
            'type' => 'alpha',
            'label' => 'A',
            'color' => '#991B1B',
            'bg' => '#FEE2E2',
            'border' => '#FECACA',
            'isClickable' => true,
            'records' => $records,
            'attendance' => null,
        ];
    }

    /**
     * Bangun matrix kalender untuk banyak user sekaligus (hindari N+1).
     * Return: ['tanggalList'=>Collection, 'matrix'=>[userId=>[dateStr=>cell]], 'totals'=>[userId=>counts], 'attendancesGrouped'=>...]
     */
    public static function kalenderUntukUsers(\Illuminate\Support\Collection $users, Carbon $start, Carbon $end, ?Carbon $now = null): array
    {
        $now = $now ? Carbon::parse($now) : now();
        $startStr = $start->toDateString();
        $endStr = $end->toDateString();
        $todayStr = $now->toDateString();
        $jamPulangSelesai = WaktuAbsen::selesai('pulang');
        // keep legacy key for backward compat, plus new key
        $jamPulangMulai = $jamPulangSelesai;
        $tanggalList = self::tanggalList($start, $end);

        if ($users->isEmpty()) {
            return [
                'tanggalList' => $tanggalList,
                'matrix' => [],
                'totals' => [],
                'laporanMatrix' => [],
                'jamPulangMulai' => $jamPulangMulai,
                'jamPulangSelesai' => $jamPulangSelesai,
                'todayStr' => $todayStr,
            ];
        }

        $userIds = $users->pluck('id')->toArray();
        $userById = $users->keyBy('id');
        $userNames = $users->pluck('nama')->filter()->map(fn($n) => trim($n))->unique()->values()->toArray();

        // Ambil attendances dalam rentang untuk user-user tersebut (sekali query)
        $attendances = Attendance::whereNotNull('tgl_masuk')->whereNotNull('jam_masuk')
            ->whereDate('tgl_masuk', '>=', $startStr)->whereDate('tgl_masuk', '<=', $endStr)
            ->where(function ($q) use ($userIds, $userNames) {
                $q->whereIn('user_id', $userIds);
                if (!empty($userNames)) {
                    $q->orWhere(function ($q2) use ($userNames) {
                        $q2->whereNull('user_id')->whereIn('nama', $userNames);
                    });
                }
            })->get();

        // Group by userId -> dateStr -> collection
        $grouped = [];
        foreach ($attendances as $att) {
            $d = Carbon::parse($att->tgl_masuk)->toDateString();
            $uid = $att->user_id;
            if ($uid !== null && isset($userById[$uid])) {
                $grouped[$uid][$d][] = $att;
            } elseif ($uid === null) {
                $namaTrim = trim($att->nama ?? '');
                foreach ($users as $u) {
                    if (trim($u->nama ?? '') === $namaTrim) {
                        $grouped[$u->id][$d][] = $att;
                        break;
                    }
                }
            }
        }
        // Jadikan collection
        foreach ($grouped as $uid => $byDate) {
            foreach ($byDate as $d => $list) {
                $grouped[$uid][$d] = collect($list);
            }
        }

        // Laporan counts per user (sekali query)
        $laporanCounts = \App\Models\Laporan::whereIn('user_id', $userIds)
            ->whereDate('tanggal_laporan', '>=', $startStr)->whereDate('tanggal_laporan', '<=', $endStr)
            ->selectRaw('user_id, COUNT(*) as cnt')->groupBy('user_id')->pluck('cnt', 'user_id');

        // Laporan utama per user per tanggal untuk subkolom Laporan pada rekap bulanan.
        // - Sumber: tabel laporans SAJA. Teknisi = jenis_laporan teknisi/NULL (scopeTeknisi),
        //   Sales = jenis_laporan sales (scopeSales). Collect customer (laporan_sales) BUKAN
        //   laporan utama sehingga diabaikan di sini.
        // - Aturan 1 laporan utama/siswa/hari: maksimal 1 baris per tanggal; bila ada duplikat
        //   historis, tanggal tersebut tetap dianggap memiliki laporan.
        // - Laporan hasil edit tetap terhitung selama record-nya masih ada.
        $laporanRows = \App\Models\Laporan::whereIn('user_id', $userIds)
            ->whereDate('tanggal_laporan', '>=', $startStr)->whereDate('tanggal_laporan', '<=', $endStr)
            ->get(['user_id', 'tanggal_laporan', 'jenis_laporan']);
        $laporanByUserDate = [];
        foreach ($laporanRows as $lr) {
            $ld = Carbon::parse($lr->tanggal_laporan)->toDateString();
            $laporanByUserDate[$lr->user_id][$ld] = $lr->jenis_laporan;
        }

        $matrix = [];
        $totals = [];
        $laporanMatrix = [];

        foreach ($users as $user) {
            $uid = $user->id;
            $userCreatedStr = $user->created_at ? Carbon::parse($user->created_at)->toDateString() : $startStr;
            $counts = ['hadir' => 0, 'telat' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0, 'laporan' => (int)($laporanCounts[$uid] ?? 0)];
            foreach ($tanggalList as $date) {
                $dStr = $date->toDateString();
                $records = $grouped[$uid][$dStr] ?? collect();
                $cell = self::resolveCellStatus($user, $date, $records, $now, $todayStr, $userCreatedStr, $jamPulangSelesai);
                // Status laporan TIDAK memengaruhi status absensi di atas.
                // array_key_exists (bukan isset) agar laporan legacy dengan jenis_laporan NULL
                // (dianggap Teknisi, lihat Laporan::scopeTeknisi) tetap terdeteksi tersedia.
                $cell['laporan'] = isset($laporanByUserDate[$uid]) && array_key_exists($dStr, $laporanByUserDate[$uid]);
                $cell['laporan_jenis'] = $laporanByUserDate[$uid][$dStr] ?? null;
                $matrix[$uid][$dStr] = $cell;
                $laporanMatrix[$uid][$dStr] = $cell['laporan'];
                if ($cell['code'] === 'H') $counts['hadir']++;
                elseif ($cell['code'] === 'T') { $counts['hadir']++; $counts['telat']++; }
                elseif ($cell['code'] === 'I') $counts['izin']++;
                elseif ($cell['code'] === 'S') $counts['sakit']++;
                elseif ($cell['code'] === 'A') $counts['alpha']++;
            }
            $totals[$uid] = $counts;
        }

        return [
            'tanggalList' => $tanggalList,
            'matrix' => $matrix,
            'totals' => $totals,
            'laporanMatrix' => $laporanMatrix,
            'attendancesGrouped' => $grouped,
            'laporanCounts' => $laporanCounts,
            'jamPulangMulai' => $jamPulangMulai,
            'jamPulangSelesai' => $jamPulangSelesai,
            'todayStr' => $todayStr,
        ];
    }

    /**
     * Detail satu tanggal untuk satu user (untuk modal klik).
     */
    public static function detailUntukTanggal(int $userId, string $tanggal, ?Carbon $now = null): array
    {
        $now = $now ? Carbon::parse($now) : now();
        $date = Carbon::parse($tanggal)->startOfDay();
        $user = User::find($userId);
        if (!$user) {
            return ['error' => 'User tidak ditemukan'];
        }
        $users = collect([$user]);
        $start = $date->copy();
        $end = $date->copy();
        $kal = self::kalenderUntukUsers($users, $start, $end, $now);
        $cell = $kal['matrix'][$userId][$tanggal] ?? null;
        if (!$cell) {
            // fallback weekend/future
            $cell = self::resolveCellStatus($user, $date, collect(), $now, $now->toDateString(), $user->created_at ? Carbon::parse($user->created_at)->toDateString() : $tanggal, WaktuAbsen::selesai('pulang'));
        }
        $attendance = $cell['attendance'] ?? null;
        // Bangun payload detail
        $payload = [
            'tanggal' => $date->translatedFormat('d F Y'),
            'hari' => $date->translatedFormat('l'),
            'nama' => $user->nama ?? $user->username,
            'user_id' => $user->user_id ?? '-',
            'code' => $cell['code'],
            'type' => $cell['type'],
            'label' => $cell['label'],
        ];
        if ($cell['type'] === 'belum_pulang') {
            $payload['status'] = 'Belum Pulang';
            $payload['message'] = $cell['note'] ?? 'Belum Pulang - batas pulang belum terlewati (' . \App\Support\WaktuAbsen::selesai('pulang') . ' WIB)';
            if ($attendance) {
                $payload['jam_masuk'] = $attendance->jam_masuk ?? '-';
                $payload['jam_pulang'] = $attendance->jam_pulang ?? '-';
                $payload['keterangan'] = $attendance->keterangan ?? '-';
                $payload['bukti_url'] = $attendance->bukti_file ? route('bukti.lihat', $attendance->id) : null;
                $payload['bukti_name'] = $attendance->bukti_original_name ?? null;
                $payload['bukti_mime'] = $attendance->bukti_mime_type ?? null;
                $payload['foto_url'] = $attendance->foto ? route('foto.lihat', $attendance->id) : null;
                $payload['latitude'] = $attendance->latitude;
                $payload['longitude'] = $attendance->longitude;
                $payload['jarak'] = $attendance->jarak ?? null;
                $payload['radius'] = $attendance->radius_kantor_saat_absen ?? null;
                if ($attendance->latitude === null && in_array($attendance->status_kehadiran, ['Izin','Dispensasi','Sakit'], true)) {
                    $payload['status_lokasi'] = 'Tanpa GPS (Izin/Sakit)';
                } elseif ($attendance->latitude !== null && $payload['jarak'] !== null) {
                    $radius = $payload['radius'] ?? 100;
                    $payload['status_lokasi'] = ((float)$payload['jarak'] <= (float)$radius) ? 'Dalam Radius' : 'Luar Radius';
                } else {
                    $payload['status_lokasi'] = '-';
                }
            }
            return $payload;
        }
        if (in_array($cell['type'], ['weekend','future','before','pending'])) {
            $payload['status'] = $cell['note'] ?? 'Tidak ada absensi';
            $payload['message'] = $cell['note'] ?? '-';
        } elseif ($cell['code'] === 'A') {
            $payload['status'] = 'Alpha';
            $payload['message'] = 'Tidak melakukan absensi pada tanggal tersebut.';
        } elseif ($attendance) {
            $payload['status'] = $attendance->status_kehadiran ?: ($attendance->status ?? '-');
            $payload['jam_masuk'] = $attendance->jam_masuk ?? '-';
            $payload['jam_pulang'] = $attendance->jam_pulang ?? (in_array($payload['status'], ['Izin','Dispensasi','Sakit'], true) ? 'Tidak Diperlukan' : '-');
            $payload['keterangan'] = $attendance->keterangan ?? '-';
            $payload['bukti_url'] = $attendance->bukti_file ? route('bukti.lihat', $attendance->id) : null;
            $payload['bukti_name'] = $attendance->bukti_original_name ?? null;
            $payload['bukti_mime'] = $attendance->bukti_mime_type ?? null;
            $payload['foto_url'] = $attendance->foto ? route('foto.lihat', $attendance->id) : null;
            $payload['latitude'] = $attendance->latitude;
            $payload['longitude'] = $attendance->longitude;
            $payload['jarak'] = $attendance->jarak ?? null;
            $payload['radius'] = $attendance->radius_kantor_saat_absen ?? null;
            // status lokasi
            if ($attendance->latitude === null && in_array($payload['status'], ['Izin','Dispensasi','Sakit'], true)) {
                $payload['status_lokasi'] = 'Tanpa GPS (Izin/Sakit)';
            } elseif ($attendance->latitude !== null && $payload['jarak'] !== null) {
                $radius = $payload['radius'] ?? 100;
                $payload['status_lokasi'] = ((float)$payload['jarak'] <= (float)$radius) ? 'Dalam Radius' : 'Luar Radius';
            } else {
                $payload['status_lokasi'] = '-';
            }
            if ($attendance->latitude_pulang !== null) {
                $payload['latitude_pulang'] = $attendance->latitude_pulang;
                $payload['longitude_pulang'] = $attendance->longitude_pulang;
                $payload['jarak_pulang'] = $attendance->jarak_pulang;
                $payload['radius_pulang'] = $attendance->radius_kantor_saat_pulang;
            }
        } else {
            $payload['status'] = $cell['code'];
        }
        return $payload;
    }
}
