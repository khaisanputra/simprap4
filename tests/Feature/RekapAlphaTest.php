<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use App\Support\MonthlyStatisticService;
use App\Support\WaktuAbsen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

/**
 * Automated test pengaman logika Alpha Rekap Absen.
 * - Senin-Jumat hari aktif, Sabtu-Minggu bukan.
 * - Alpha independen per siswa, tidak bergantung attendance siswa lain.
 * - Menggunakan WaktuAbsen::selesai('pulang') tanpa hardcode (batas akhir pulang).
 */
class RekapAlphaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-08 18:00:00'); // Selasa, setelah jam pulang
        WaktuAbsen::flush();
        // pastikan jam pulang known, gunakan config existing
        // set explicit untuk deterministik
        \App\Models\Setting::updateOrCreate(['key' => 'jam_pulang_mulai'], ['value' => '15:00']);
        \App\Models\Setting::updateOrCreate(['key' => 'jam_pulang_selesai'], ['value' => '23:00']);
        WaktuAbsen::flush();
    }

    private function makeUser(string $username, string $nama, string $userId, ?string $createdAt = null): User
    {
        $user = User::create([
            'username' => $username,
            'email' => $username . '@test.com',
            'nama' => $nama,
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'status' => 'Aktif',
            'user_id' => $userId,
        ]);
        if ($createdAt) {
            $user->created_at = Carbon::parse($createdAt);
            $user->updated_at = Carbon::parse($createdAt);
            $user->save();
        } else {
            // default sebelum periode
            $user->created_at = Carbon::parse('2026-08-01');
            $user->updated_at = Carbon::parse('2026-08-01');
            $user->save();
        }
        return $user->fresh();
    }

    private function buatHadir(User $user, string $date, string $status = 'Masuk'): Attendance
    {
        return Attendance::create([
            'user_id' => $user->id,
            'nama' => $user->nama,
            'status' => $status,
            'status_kehadiran' => $status,
            'tgl_masuk' => $date,
            'jam_masuk' => '08:00:00',
            'tgl_pulang' => $date,
            'jam_pulang' => '15:10:00',
            'latitude' => -8.1986,
            'longitude' => 111.1179,
        ]);
    }

    private function buatStatus(User $user, string $date, string $status): Attendance
    {
        return Attendance::create([
            'user_id' => $user->id,
            'nama' => $user->nama,
            'status' => $status,
            'status_kehadiran' => $status,
            'tgl_masuk' => $date,
            'jam_masuk' => '08:00:00',
            'latitude' => -8.1986,
            'longitude' => 111.1179,
        ]);
    }

    // TEST 1 — SENIN
    public function test_senin_alpha(): void
    {
        $u = $this->makeUser('s1', 'Siswa Satu', 'G1-001');
        $start = Carbon::parse('2026-09-07'); // 2026-09-07 is Monday
        $this->assertTrue($start->isMonday());
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(1, $data['jumlah_alpha'], 'Senin tanpa absensi harus Alpha 1');
    }

    // TEST 2 — JUMAT
    public function test_jumat_alpha(): void
    {
        Carbon::setTestNow('2026-09-12 18:00:00'); // Friday after pulang
        WaktuAbsen::flush();
        $u = $this->makeUser('s2', 'Siswa Dua', 'G1-002');
        $start = Carbon::parse('2026-09-11'); // 2026-09-11 is Thursday? Let's find Friday 2026-09-11 is Thursday, use 2026-09-11 is Friday? Check
        // 2026-09-11 is Friday actually in 2026? Let's ensure Friday: 2026-09-04 is Friday, 2026-09-11 is Friday? Quick check: 2026-09-07 Mon, 11 Fri
        $this->assertTrue(Carbon::parse('2026-09-11')->isFriday());
        $start = Carbon::parse('2026-09-11');
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(1, $data['jumlah_alpha'], 'Jumat tanpa absensi harus Alpha 1');
    }

    // TEST 3 — SABTU
    public function test_sabtu_no_alpha(): void
    {
        $u = $this->makeUser('s3', 'Siswa Tiga', 'G1-003');
        $start = Carbon::parse('2026-09-05'); // 2026-09-05 is Saturday
        $this->assertTrue($start->isSaturday());
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(0, $data['jumlah_alpha'], 'Sabtu bukan hari aktif, Alpha 0');
    }

    // TEST 4 — MINGGU
    public function test_minggu_no_alpha(): void
    {
        $u = $this->makeUser('s4', 'Siswa Empat', 'G1-004');
        $start = Carbon::parse('2026-09-06'); // Sunday
        $this->assertTrue($start->isSunday());
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(0, $data['jumlah_alpha'], 'Minggu bukan hari aktif, Alpha 0');
    }

    // TEST 5 — SATU MINGGU SENIN-MINGGU = 5
    public function test_satu_minggu_alpha_5(): void
    {
        Carbon::setTestNow('2026-09-14 18:00:00'); // Monday after week
        WaktuAbsen::flush();
        $u = $this->makeUser('s5', 'Siswa Lima', 'G1-005');
        $start = Carbon::parse('2026-09-07'); // Mon
        $end = Carbon::parse('2026-09-13'); // Sun
        $this->assertTrue($start->isMonday());
        $this->assertTrue($end->isSunday());
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(5, $data['jumlah_alpha'], 'Senin-Minggu tanpa absensi harus 5 Alpha, bukan 7');
    }

    // TEST 6 — ABSENSI SISWA LAIN TIDAK MEMPENGARUHI
    public function test_absensi_siswa_lain_tidak_mempengaruhi(): void
    {
        Carbon::setTestNow('2026-09-03 18:00:00');
        WaktuAbsen::flush();
        $a = $this->makeUser('a6', 'A', 'G1-A');
        $b = $this->makeUser('b6', 'B', 'G1-B');
        $c = $this->makeUser('c6', 'C', 'G1-C');
        $this->buatHadir($a, '2026-09-01', 'Masuk');
        $this->buatHadir($b, '2026-09-01', 'Masuk');
        $this->buatHadir($c, '2026-09-01', 'Masuk');
        $this->buatHadir($a, '2026-09-03', 'Masuk');
        $this->buatHadir($b, '2026-09-03', 'Masuk');
        $this->buatHadir($c, '2026-09-03', 'Masuk');
        // 2026-09-02 tidak ada yang hadir (all absent)
        $start = Carbon::parse('2026-09-01');
        $end = Carbon::parse('2026-09-03');
        $data = MonthlyStatisticService::hitungUntukUserPeriode($a->id, $start, $end);
        // 01 hadir, 02 alpha, 03 hadir => alpha 1
        $this->assertEquals(1, $data['jumlah_alpha'], 'Alpha harus tetap 1 meski tidak ada attendance siapapun di 02');
        $this->assertEquals(2, $data['jumlah_hadir']);
    }

    // TEST 7 — HADIR LENGKAP BUKAN ALPHA
    public function test_hadir_lengkap_bukan_alpha(): void
    {
        $u = $this->makeUser('u7', 'U7', 'G1-007');
        $this->buatHadir($u, '2026-09-07', 'Masuk');
        $start = Carbon::parse('2026-09-07');
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(1, $data['jumlah_hadir']);
        $this->assertEquals(0, $data['jumlah_alpha']);
    }

    // TEST 8 — TELAT + PULANG BUKAN ALPHA
    public function test_telat_pulang_bukan_alpha(): void
    {
        $u = $this->makeUser('u8', 'U8', 'G1-008');
        $this->buatHadir($u, '2026-09-07', 'Telat');
        $start = Carbon::parse('2026-09-07');
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(1, $data['jumlah_hadir']);
        $this->assertEquals(0, $data['jumlah_alpha']);
    }

    // TEST 9 — IZIN BUKAN ALPHA
    public function test_izin_bukan_alpha(): void
    {
        $u = $this->makeUser('u9', 'U9', 'G1-009');
        $this->buatStatus($u, '2026-09-07', 'Izin');
        $start = Carbon::parse('2026-09-07');
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(1, $data['jumlah_izin']);
        $this->assertEquals(0, $data['jumlah_alpha']);
    }

    // TEST 10 — DISPENSASI BUKAN ALPHA (hitung sebagai Izin)
    public function test_dispensasi_bukan_alpha(): void
    {
        $u = $this->makeUser('u10', 'U10', 'G1-010');
        $this->buatStatus($u, '2026-09-07', 'Dispensasi');
        $start = Carbon::parse('2026-09-07');
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(1, $data['jumlah_izin']);
        $this->assertEquals(0, $data['jumlah_alpha']);
    }

    // TEST 11 — SAKIT BUKAN ALPHA
    public function test_sakit_bukan_alpha(): void
    {
        $u = $this->makeUser('u11', 'U11', 'G1-011');
        $this->buatStatus($u, '2026-09-07', 'Sakit');
        $start = Carbon::parse('2026-09-07');
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(1, $data['jumlah_sakit']);
        $this->assertEquals(0, $data['jumlah_alpha']);
    }

    // TEST 12 — BELUM WAKTUNYA ALPHA HARI INI (sebelum jam pulang selesai)
    public function test_belum_waktunya_alpha_hari_ini(): void
    {
        $jamPulang = WaktuAbsen::selesai('pulang'); // e.g. 23:00 batas akhir
        // set now 1 hour before jam pulang selesai
        $today = Carbon::parse('2026-09-08'); // Monday
        $before = Carbon::parse('2026-09-08 ' . $jamPulang)->subHour();
        Carbon::setTestNow($before);
        WaktuAbsen::flush();
        $u = $this->makeUser('u12', 'U12', 'G1-012');
        $start = Carbon::parse('2026-09-08');
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(0, $data['jumlah_alpha'], 'Sebelum jam pulang selesai tidak boleh Alpha');
    }

    // TEST 13 — SUDAH WAKTU ALPHA HARI INI (setelah jam pulang selesai)
    public function test_sudah_waktunya_alpha_hari_ini(): void
    {
        $jamPulang = WaktuAbsen::selesai('pulang');
        $today = Carbon::parse('2026-09-08'); // Monday
        $after = Carbon::parse('2026-09-08 ' . $jamPulang)->addHour();
        Carbon::setTestNow($after);
        WaktuAbsen::flush();
        $u = $this->makeUser('u13', 'U13', 'G1-013');
        $start = Carbon::parse('2026-09-08');
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(1, $data['jumlah_alpha'], 'Setelah jam pulang selesai boleh Alpha');
    }

    // TEST 14 — CREATED_AT
    public function test_created_at(): void
    {
        Carbon::setTestNow('2026-09-05 18:00:00');
        WaktuAbsen::flush();
        $u = $this->makeUser('u14', 'U14', 'G1-014', '2026-09-04');
        $start = Carbon::parse('2026-09-01');
        $end = Carbon::parse('2026-09-05'); // 01-05: 01 Tue,02 Wed,03 Thu,04 Fri,05 Sat
        // 01-03 sebelum created_at 04 -> skip, 04 Fri Alpha 1, 05 Sat bukan hari aktif 0
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(1, $data['jumlah_alpha']);
    }

    // TEST 15 — TANGGAL MASA DEPAN
    public function test_tanggal_masa_depan(): void
    {
        Carbon::setTestNow('2026-09-01 18:00:00');
        WaktuAbsen::flush();
        $u = $this->makeUser('u15', 'U15', 'G1-015');
        $start = Carbon::parse('2026-09-02');
        $end = Carbon::parse('2026-09-03');
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(0, $data['jumlah_alpha'], 'Masa depan tidak dihitung');
    }

    // TEST 16 — WEEKEND DI DALAM REKAP BULANAN
    public function test_weekend_dalam_bulanan(): void
    {
        // Set after batas pulang selesai agar hari terakhir (30) ikut dihitung Alpha
        // 23:00 adalah batas selesai, jadi pakai 23:30 agar sudah lewat
        Carbon::setTestNow('2026-09-30 23:30:00');
        WaktuAbsen::flush();
        $u = $this->makeUser('u16', 'U16', 'G1-016');
        $start = Carbon::parse('2026-09-01');
        $end = Carbon::parse('2026-09-30');
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        // Hitung weekday Sept 2026: 01 Tue -> 30 Wed, weekends 5,6,12,13,19,20,26,27 = 8, weekdays 22
        $weekdays = 0;
        $c = Carbon::parse('2026-09-01');
        while ($c->lte(Carbon::parse('2026-09-30'))) {
            if ($c->isWeekday()) $weekdays++;
            $c->addDay();
        }
        $this->assertEquals($weekdays, $data['jumlah_alpha'], 'Alpha maksimal = weekdays, bukan 30');
    }
}
