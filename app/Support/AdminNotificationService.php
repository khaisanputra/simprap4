<?php

namespace App\Support;

use App\Models\AdminNotification;
use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AdminNotificationService
{
    /**
     * Buat notifikasi untuk Izin/Dispensasi/Sakit setelah Attendance berhasil dibuat.
     * Aman: try/catch agar tidak mengganggu proses absensi utama. Anti-duplikat.
     */
    public static function notifyForAttendance(Attendance $attendance): ?AdminNotification
    {
        try {
            $type = null;
            $status = $attendance->status_kehadiran ?? $attendance->status;
            $map = [
                Attendance::STATUS_KEHADIRAN_IZIN => AdminNotification::TYPE_IZIN,
                Attendance::STATUS_KEHADIRAN_DISPENSASI => AdminNotification::TYPE_DISPENSASI,
                Attendance::STATUS_KEHADIRAN_SAKIT => AdminNotification::TYPE_SAKIT,
            ];
            if (!isset($map[$status])) {
                return null; // Hanya Izin/Dispensasi/Sakit
            }
            $type = $map[$status];

            // Hanya untuk Masuk yang membutuhkan notifikasi; Hadir/Telat/Alpha tidak
            $nama = $attendance->user?->nama ?? $attendance->nama ?? 'Siswa';
            $tanggal = $attendance->tgl_masuk ? Carbon::parse($attendance->tgl_masuk)->toDateString() : now()->toDateString();
            $studentUserId = $attendance->user_id;

            // Pesan sesuai tanggal attendance (hari ini vs pada tanggal)
            $todayStr = now()->toDateString();
            $isToday = $tanggal === $todayStr;
            $tglFmt = $isToday ? null : Carbon::parse($tanggal)->translatedFormat('d F Y');
            $message = match ($type) {
                AdminNotification::TYPE_IZIN => $nama . ($isToday ? ' mengajukan izin hari ini.' : ' mengajukan izin pada ' . $tglFmt . '.'),
                AdminNotification::TYPE_DISPENSASI => $nama . ($isToday ? ' mengajukan dispensasi hari ini.' : ' mengajukan dispensasi pada ' . $tglFmt . '.'),
                AdminNotification::TYPE_SAKIT => $nama . ($isToday ? ' mengajukan izin sakit hari ini.' : ' mengajukan izin sakit pada ' . $tglFmt . '.'),
                default => $nama . ' — ' . $status,
            };
            $title = $nama . ' — ' . (new AdminNotification(['type' => $type]))->type_label;

            // Anti-duplikat: cek existing (type + student_user_id + tanggal) atau fallback nama+tanggal jika user_id null
            $exists = AdminNotification::where('type', $type)
                ->where('tanggal', $tanggal)
                ->where(function ($q) use ($studentUserId, $nama) {
                    if ($studentUserId) {
                        $q->where('student_user_id', $studentUserId);
                    } else {
                        $q->whereNull('student_user_id')->where('student_name', $nama);
                    }
                })->exists();

            if ($exists) {
                return null;
            }

            return AdminNotification::create([
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'student_user_id' => $studentUserId,
                'student_name' => $nama,
                'tanggal' => $tanggal,
                'reference_id' => $attendance->id,
                'read_at' => null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal membuat notifikasi admin untuk attendance', [
                'attendance_id' => $attendance->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Pastikan notifikasi Alpha untuk tanggal tertentu sudah dibuat (lazy).
     * Mengikuti logic existing: hanya setelah WaktuAbsen::selesai('pulang'), bukan weekend, bukan masa depan.
     * Menggunakan Attendance::tidakMasukUntukTanggal yang sudah konsisten dengan Rekap.
     */
    public static function ensureAlphaForDate(string $tanggal, ?Carbon $now = null): int
    {
        try {
            $now = $now ? Carbon::parse($now) : now();
            $todayStr = $now->toDateString();
            $target = Carbon::parse($tanggal);

            // Validasi mengikuti Attendance::tidakMasukUntukTanggal
            if ($target->isWeekend()) {
                return 0;
            }
            if ($tanggal > $todayStr) {
                return 0;
            }
            if ($tanggal === $todayStr) {
                $jamSelesai = WaktuAbsen::selesai('pulang');
                $waktuSelesai = Carbon::parse($todayStr . ' ' . $jamSelesai . ':00');
                if ($now->lt($waktuSelesai)) {
                    return 0; // Belum lewat batas pulang
                }
            }

            $usersAlpha = Attendance::tidakMasukUntukTanggal($tanggal, $now);
            if ($usersAlpha->isEmpty()) {
                return 0;
            }

            $created = 0;
            foreach ($usersAlpha as $user) {
                $studentUserId = $user->id;
                $nama = $user->nama ?? $user->username ?? 'Siswa';

                // Anti-duplikat per siswa/tanggal/jenis
                $exists = AdminNotification::where('type', AdminNotification::TYPE_ALPHA)
                    ->where('tanggal', $tanggal)
                    ->where('student_user_id', $studentUserId)
                    ->exists();
                if ($exists) {
                    continue;
                }

                $title = $nama . ' — Alpha';
                $isToday = $tanggal === $todayStr;
                if ($isToday) {
                    $message = $nama . ' tercatat Alpha hari ini.';
                } else {
                    $tglFmt = Carbon::parse($tanggal)->translatedFormat('d F Y');
                    $message = $nama . ' tercatat Alpha pada ' . $tglFmt . '.';
                }

                try {
                    AdminNotification::create([
                        'type' => AdminNotification::TYPE_ALPHA,
                        'title' => $title,
                        'message' => $message,
                        'student_user_id' => $studentUserId,
                        'student_name' => $nama,
                        'tanggal' => $tanggal,
                        'reference_id' => null,
                        'read_at' => null,
                    ]);
                    $created++;
                } catch (\Throwable $e) {
                    // Unique violation atau error lain -> skip
                    if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'unique')) {
                        continue;
                    }
                    Log::warning('Gagal membuat notifikasi Alpha', [
                        'tanggal' => $tanggal,
                        'user_id' => $studentUserId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $created;
        } catch (\Throwable $e) {
            Log::warning('Gagal ensure Alpha untuk tanggal', [
                'tanggal' => $tanggal,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Pastikan Alpha hari ini (dipanggil saat admin membuka notifikasi/dashboard).
     */
    public static function ensureAlphaForToday(?Carbon $now = null): int
    {
        $now = $now ? Carbon::parse($now) : now();
        return self::ensureAlphaForDate($now->toDateString(), $now);
    }

    /**
     * Sinkron Alpha untuk rentang (backfill jika admin tidak login beberapa hari).
     * Default 7 hari terakhir termasuk hari ini.
     */
    public static function syncAlphaRecent(int $days = 7, ?Carbon $now = null): int
    {
        $now = $now ? Carbon::parse($now) : now();
        $total = 0;
        for ($i = 0; $i < $days; $i++) {
            $date = $now->copy()->subDays($i)->toDateString();
            $total += self::ensureAlphaForDate($date, $now);
        }
        return $total;
    }

    /**
     * Hapus notifikasi admin yang sudah melewati masa retensi 14 hari.
     * HANYA menghapus admin_notifications berdasarkan created_at.
     * TIDAK menyentuh attendances/users/laporan/foto/bukti.
     * Prinsip: DELETE WHERE created_at < now() - 14 days
     */
    public static function pruneExpired(int $days = 14): int
    {
        try {
            $cutoff = now()->subDays($days);
            return AdminNotification::where('created_at', '<', $cutoff)->delete();
        } catch (\Throwable $e) {
            Log::warning('Gagal prune notifikasi admin', [
                'days' => $days,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }
}
