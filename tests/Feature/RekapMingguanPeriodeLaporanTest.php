<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Laporan;
use App\Models\Setting;
use App\Models\User;
use App\Support\MonthlyStatisticService;
use App\Support\WaktuAbsen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Test indikator Absen + Lap per tanggal pada rekap Mingguan & Periodik
 * (Admin dan Siswa), konsisten dengan rekap bulanan.
 * Fokus:
 * - Kolom Lap muncul untuk semua jenis rekap (mingguan/periode).
 * - Pencocokan tanggal akurat; laporan di luar periode tidak dihitung.
 * - Laporan Teknisi, Sales, dan data legacy jenis_laporan NULL dikenali.
 * - Isolasi data antar siswa.
 * - Print & Excel memuat kolom Lap yang sama.
 */
class RekapMingguanPeriodeLaporanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-11 18:00:00'); // Jumat, minggu 07-13 September 2026
        WaktuAbsen::flush();
        Setting::updateOrCreate(['key' => 'jam_pulang_mulai'], ['value' => '15:00']);
        Setting::updateOrCreate(['key' => 'jam_pulang_selesai'], ['value' => '23:00']);
        WaktuAbsen::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        WaktuAbsen::flush();
        parent::tearDown();
    }

    private function makeSiswa(string $username, string $nama, string $userId): User
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
        $user->created_at = Carbon::parse('2026-08-01 08:00:00');
        $user->updated_at = Carbon::parse('2026-08-01 08:00:00');
        $user->save();

        return $user->fresh();
    }

    private function makeAdmin(): User
    {
        return User::create([
            'username' => 'admin.rekap',
            'email' => 'admin.rekap@test.com',
            'nama' => 'Admin Rekap',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'Aktif',
        ]);
    }

    private function buatLaporan(User $user, string $tanggal, ?string $jenis): Laporan
    {
        return Laporan::create([
            'user_id' => $user->id,
            'tanggal_laporan' => $tanggal,
            'teks_laporan' => 'Isi laporan ' . $tanggal,
            'jenis_laporan' => $jenis,
        ]);
    }

    private function buatHadir(User $user, string $tanggal): Attendance
    {
        return Attendance::create([
            'user_id' => $user->id,
            'nama' => $user->nama,
            'status' => 'Masuk',
            'status_kehadiran' => 'Masuk',
            'tgl_masuk' => $tanggal,
            'jam_masuk' => '08:00:00',
            'tgl_pulang' => $tanggal,
            'jam_pulang' => '15:10:00',
        ]);
    }

    public function test_admin_mingguan_menampilkan_kolom_lap_pada_tanggal_yang_benar(): void
    {
        $admin = $this->makeAdmin();
        $siswaA = $this->makeSiswa('siswa.w', 'Siswa W', 'INT0041');
        $siswaB = $this->makeSiswa('siswa.x', 'Siswa X', 'INT0042');

        $this->buatLaporan($siswaA, '2026-09-08', Laporan::JENIS_TEKNISI);
        $this->buatLaporan($siswaB, '2026-09-09', Laporan::JENIS_SALES);
        // Di luar minggu terpilih — tidak boleh dihitung
        $this->buatLaporan($siswaA, '2026-09-14', Laporan::JENIS_TEKNISI);

        $response = $this->actingAs($admin, 'web')->get(route('rekap.admin', [
            'jenis' => 'mingguan',
            'tanggal' => '2026-09-10',
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();
        $this->assertStringContainsString('>Lap</th>', $html);
        $this->assertStringContainsString('>Absen</th>', $html);
        $this->assertStringContainsString('08 September 2026 • Siswa W • Laporan: OK', $html);
        $this->assertStringContainsString('09 September 2026 • Siswa X • Laporan: OK', $html);
        // Laporan 14 September (luar minggu) tidak dihitung — total OK hanya 2 dalam minggu ini
        $this->assertSame(2, substr_count($html, '• Laporan: OK'));
        $this->assertStringNotContainsString('14 September 2026 • Siswa W • Laporan: OK', $html);
    }

    public function test_admin_periode_lintas_bulan_menghitung_laporan_sesuai_periode(): void
    {
        $admin = $this->makeAdmin();
        $siswa = $this->makeSiswa('siswa.y', 'Siswa Y', 'INT0043');

        $this->buatLaporan($siswa, '2026-08-31', Laporan::JENIS_SALES);
        $this->buatLaporan($siswa, '2026-09-02', null); // legacy teknisi
        $this->buatLaporan($siswa, '2026-09-07', Laporan::JENIS_TEKNISI); // di luar periode

        $response = $this->actingAs($admin, 'web')->get(route('rekap.admin', [
            'jenis' => 'periode',
            'start_date' => '2026-08-31',
            'end_date' => '2026-09-06',
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();
        $this->assertStringContainsString('>Lap</th>', $html);
        $this->assertStringContainsString('31 Agustus 2026 • Siswa Y • Laporan: OK', $html);
        $this->assertStringContainsString('02 September 2026 • Siswa Y • Laporan: OK', $html);
        $this->assertSame(2, substr_count($html, '• Laporan: OK'));
    }

    public function test_siswa_mingguan_menampilkan_indikator_dan_terisolasi(): void
    {
        $siswaA = $this->makeSiswa('siswa.z', 'Siswa Z', 'INT0044');
        $siswaB = $this->makeSiswa('siswa.aa', 'Siswa AA', 'INT0045');

        $this->buatLaporan($siswaA, '2026-09-08', Laporan::JENIS_TEKNISI);
        // Milik siswa lain pada tanggal tanpa laporan A — tidak boleh bocor
        $this->buatLaporan($siswaB, '2026-09-09', Laporan::JENIS_TEKNISI);
        // Skenario: absen + laporan di tanggal sama (08), dan absen tanpa laporan (10)
        $this->buatHadir($siswaA, '2026-09-08');
        $this->buatHadir($siswaA, '2026-09-10');

        $response = $this->actingAs($siswaA, 'web')->get(route('rekap.siswa', [
            'jenis' => 'mingguan',
            'tanggal' => '2026-09-10',
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();
        $this->assertStringContainsString('08 September 2026 • Laporan: Ada', $html);
        $this->assertStringContainsString('10 September 2026 • Laporan: Tidak ada', $html);
        $this->assertStringContainsString('09 September 2026 • Laporan: Tidak ada', $html);
        $this->assertStringNotContainsString('09 September 2026 • Laporan: Ada', $html);
        // Absensi di tanggal 08 (ber-laporan) dan 10 (tanpa laporan) tetap terdeteksi
        $this->assertStringContainsString('data-tanggal="2026-09-08"', $html);
        $this->assertStringContainsString('data-tanggal="2026-09-10"', $html);
        // Minggu = 7 hari: 1 Ada, 6 Tidak ada
        $this->assertSame(1, substr_count($html, 'Laporan: Ada'));
        $this->assertSame(6, substr_count($html, 'Laporan: Tidak ada'));
    }

    public function test_pergantian_minggu_menampilkan_laporan_pada_minggu_yang_tepat(): void
    {
        $siswa = $this->makeSiswa('siswa.ag', 'Siswa AG', 'INT0051');
        $this->buatLaporan($siswa, '2026-09-08', Laporan::JENIS_TEKNISI);
        $this->buatLaporan($siswa, '2026-09-14', Laporan::JENIS_SALES);

        // Minggu 07-13 September: hanya laporan 08 yang tampil
        $minggu1 = $this->actingAs($siswa, 'web')->get(route('rekap.siswa', [
            'jenis' => 'mingguan',
            'tanggal' => '2026-09-10',
        ]));
        $minggu1->assertStatus(200);
        $html1 = $minggu1->getContent();
        $this->assertStringContainsString('08 September 2026 • Laporan: Ada', $html1);
        $this->assertStringNotContainsString('14 September 2026 • Laporan: Ada', $html1);
        $this->assertSame(1, substr_count($html1, 'Laporan: Ada'));

        // Minggu 14-20 September: hanya laporan 14 yang tampil
        $minggu2 = $this->actingAs($siswa, 'web')->get(route('rekap.siswa', [
            'jenis' => 'mingguan',
            'tanggal' => '2026-09-16',
        ]));
        $minggu2->assertStatus(200);
        $html2 = $minggu2->getContent();
        $this->assertStringContainsString('14 September 2026 • Laporan: Ada', $html2);
        $this->assertStringNotContainsString('08 September 2026 • Laporan: Ada', $html2);
        $this->assertSame(1, substr_count($html2, 'Laporan: Ada'));
    }

    public function test_siswa_periode_lintas_bulan_dan_laporan_di_luar_periode(): void
    {
        $siswaA = $this->makeSiswa('siswa.ab', 'Siswa AB', 'INT0046');
        $siswaB = $this->makeSiswa('siswa.ac', 'Siswa AC', 'INT0047');

        $this->buatLaporan($siswaA, '2026-08-31', Laporan::JENIS_SALES);
        $this->buatLaporan($siswaA, '2026-09-02', null); // legacy teknisi
        $this->buatLaporan($siswaA, '2026-09-07', Laporan::JENIS_TEKNISI); // di luar periode
        $this->buatLaporan($siswaB, '2026-09-01', Laporan::JENIS_TEKNISI); // milik siswa lain

        $response = $this->actingAs($siswaA, 'web')->get(route('rekap.siswa', [
            'jenis' => 'periode',
            'start_date' => '2026-08-31',
            'end_date' => '2026-09-06',
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();
        $this->assertStringContainsString('31 Agustus 2026 • Laporan: Ada', $html);
        $this->assertStringContainsString('02 September 2026 • Laporan: Ada', $html);
        $this->assertStringContainsString('01 September 2026 • Laporan: Tidak ada', $html);
        $this->assertStringNotContainsString('07 September 2026 • Laporan: Ada', $html);
        // Periode 7 hari: 2 Ada, 5 Tidak ada
        $this->assertSame(2, substr_count($html, 'Laporan: Ada'));
        $this->assertSame(5, substr_count($html, 'Laporan: Tidak ada'));
    }

    public function test_print_admin_mingguan_dan_periode_memuat_kolom_lap(): void
    {
        $admin = $this->makeAdmin();
        $siswa = $this->makeSiswa('siswa.ad', 'Siswa AD', 'INT0048');
        $this->buatLaporan($siswa, '2026-09-08', Laporan::JENIS_TEKNISI);

        $mingguan = $this->actingAs($admin, 'web')->get(route('rekap.admin.print', [
            'jenis' => 'mingguan',
            'tanggal' => '2026-09-10',
        ]));
        $mingguan->assertStatus(200);
        $this->assertStringContainsString('sub-lap', $mingguan->getContent());
        // 1 tanggal OK di data + 1 di legenda
        $this->assertSame(2, substr_count($mingguan->getContent(), 'class="badge ok"'));

        $periode = $this->actingAs($admin, 'web')->get(route('rekap.admin.print', [
            'jenis' => 'periode',
            'start_date' => '2026-09-08',
            'end_date' => '2026-09-09',
        ]));
        $periode->assertStatus(200);
        $this->assertStringContainsString('sub-lap', $periode->getContent());
        $this->assertSame(2, substr_count($periode->getContent(), 'class="badge ok"'));
    }

    public function test_excel_view_mingguan_dan_periode_konsisten_dengan_halaman(): void
    {
        $siswa = $this->makeSiswa('siswa.ae', 'Siswa AE', 'INT0049');
        $this->buatLaporan($siswa, '2026-09-08', Laporan::JENIS_TEKNISI);

        $users = collect([$siswa]);

        // Mingguan: 07-13 September 2026
        $start = Carbon::parse('2026-09-07')->startOfDay();
        $end = Carbon::parse('2026-09-13')->endOfDay();
        $kal = MonthlyStatisticService::kalenderUntukUsers($users, $start, $end, Carbon::now());
        $html = view('rekap.excel-calendar', [
            'users' => $users,
            'tanggalList' => $kal['tanggalList'],
            'matrix' => $kal['matrix'],
            'totals' => $kal['totals'],
            'labelPeriode' => '07 - 13 September 2026',
            'jenis' => 'mingguan',
        ])->render();
        $this->assertSame(7, substr_count($html, '>Lap</th>'));
        $this->assertSame(7, substr_count($html, '>Absen</th>'));
        $this->assertSame(1, substr_count($html, '>OK</td>'));

        // Periode lintas bulan 31 Agustus - 06 September 2026 (7 hari)
        $this->buatLaporan($siswa, '2026-08-31', Laporan::JENIS_SALES);
        $this->buatLaporan($siswa, '2026-09-02', null); // legacy teknisi
        $start2 = Carbon::parse('2026-08-31')->startOfDay();
        $end2 = Carbon::parse('2026-09-06')->endOfDay();
        $kal2 = MonthlyStatisticService::kalenderUntukUsers($users, $start2, $end2, Carbon::now());
        $html2 = view('rekap.excel-calendar', [
            'users' => $users,
            'tanggalList' => $kal2['tanggalList'],
            'matrix' => $kal2['matrix'],
            'totals' => $kal2['totals'],
            'labelPeriode' => '31 Agustus - 06 September 2026',
            'jenis' => 'periode',
        ])->render();
        $this->assertSame(7, substr_count($html2, '>Lap</th>'));
        $this->assertSame(2, substr_count($html2, '>OK</td>'));
    }

    public function test_export_excel_route_admin_mingguan_dan_periode_tetap_berfungsi(): void
    {
        $admin = $this->makeAdmin();
        $siswa = $this->makeSiswa('siswa.af', 'Siswa AF', 'INT0050');
        $this->buatLaporan($siswa, '2026-09-08', Laporan::JENIS_TEKNISI);

        $mingguan = $this->actingAs($admin, 'web')->get(route('rekap.admin.export', [
            'jenis' => 'mingguan',
            'tanggal' => '2026-09-10',
        ]));
        $mingguan->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $mingguan->headers->get('content-type'));

        $periode = $this->actingAs($admin, 'web')->get(route('rekap.admin.export', [
            'jenis' => 'periode',
            'start_date' => '2026-08-31',
            'end_date' => '2026-09-06',
        ]));
        $periode->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $periode->headers->get('content-type'));
    }
}
