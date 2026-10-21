<?php

namespace Tests\Feature;

use App\Models\Laporan;
use App\Models\Setting;
use App\Models\User;
use App\Support\WaktuAbsen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Test indikator status laporan pada Riwayat Rekap siswa (kalender bulanan).
 * Fokus:
 * - Laporan Teknisi (termasuk data legacy jenis_laporan NULL) & Sales terdeteksi pada tanggal yang benar.
 * - Laporan siswa lain tidak bocor ke rekap siswa yang sedang login.
 * - Laporan yang diedit tetap terdeteksi.
 * - Tanggal tanpa laporan tetap tampil dengan indikator "Tidak ada".
 */
class RekapSiswaLaporanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-30 18:00:00');
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

    private function buatLaporan(User $user, string $tanggal, ?string $jenis): Laporan
    {
        return Laporan::create([
            'user_id' => $user->id,
            'tanggal_laporan' => $tanggal,
            'teks_laporan' => 'Isi laporan ' . $tanggal,
            'jenis_laporan' => $jenis,
        ]);
    }

    public function test_indikator_laporan_bulanan_akurat_dan_terisolasi_per_siswa(): void
    {
        $siswaA = $this->makeSiswa('siswa.a', 'Siswa A', 'INT0031');
        $siswaB = $this->makeSiswa('siswa.b', 'Siswa B', 'INT0032');

        // A: laporan Teknisi, laporan Sales, dan laporan legacy (jenis_laporan NULL = Teknisi)
        $this->buatLaporan($siswaA, '2026-09-07', Laporan::JENIS_TEKNISI);
        $this->buatLaporan($siswaA, '2026-09-08', Laporan::JENIS_SALES);
        $this->buatLaporan($siswaA, '2026-09-10', null);
        // B: laporan pada tanggal di mana A tidak punya laporan
        $this->buatLaporan($siswaB, '2026-09-09', Laporan::JENIS_TEKNISI);

        $response = $this->actingAs($siswaA, 'web')->get(route('rekap.siswa', [
            'jenis' => 'bulanan',
            'bulan' => 9,
            'tahun' => 2026,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Absen');
        $response->assertSee('Lap');

        $html = $response->getContent();

        // Tanggal dengan laporan milik A (termasuk legacy NULL) = Ada
        $this->assertStringContainsString('07 September 2026 • Laporan: Ada', $html);
        $this->assertStringContainsString('08 September 2026 • Laporan: Ada', $html);
        $this->assertStringContainsString('10 September 2026 • Laporan: Ada', $html);

        // Tanggal laporan milik B tetap Tidak ada untuk A (tidak bocor)
        $this->assertStringContainsString('09 September 2026 • Laporan: Tidak ada', $html);
        $this->assertStringNotContainsString('09 September 2026 • Laporan: Ada', $html);

        // September 2026 = 30 hari; 3 hari Ada, 27 hari Tidak ada
        $this->assertSame(3, substr_count($html, 'Laporan: Ada'));
        $this->assertSame(27, substr_count($html, 'Laporan: Tidak ada'));
    }

    public function test_laporan_yang_diedit_tetap_terdeteksi(): void
    {
        $siswa = $this->makeSiswa('siswa.c', 'Siswa C', 'INT0033');
        $laporan = $this->buatLaporan($siswa, '2026-09-14', Laporan::JENIS_TEKNISI);

        // Simulasi edit laporan: record yang sama diperbarui (sesuai LaporanController::update)
        $laporan->update(['teks_laporan' => 'Teks laporan sudah diedit']);

        $response = $this->actingAs($siswa, 'web')->get(route('rekap.siswa', [
            'jenis' => 'bulanan',
            'bulan' => 9,
            'tahun' => 2026,
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();
        $this->assertStringContainsString('14 September 2026 • Laporan: Ada', $html);

        // Bulan lain tidak menampilkan indikator Ada untuk laporan September
        $responseLain = $this->actingAs($siswa, 'web')->get(route('rekap.siswa', [
            'jenis' => 'bulanan',
            'bulan' => 8,
            'tahun' => 2026,
        ]));
        $responseLain->assertStatus(200);
        $this->assertSame(0, substr_count($responseLain->getContent(), 'Laporan: Ada'));
    }

    public function test_halaman_rekap_admin_tetap_berfungsi_dengan_kolom_laporan(): void
    {
        $admin = User::create([
            'username' => 'admin.test',
            'email' => 'admin.test@test.com',
            'nama' => 'Admin Test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'Aktif',
        ]);
        $siswa = $this->makeSiswa('siswa.e', 'Siswa E', 'INT0035');
        $this->buatLaporan($siswa, '2026-09-07', Laporan::JENIS_TEKNISI);

        $response = $this->actingAs($admin, 'web')->get(route('rekap.admin', [
            'jenis' => 'bulanan',
            'bulan' => 9,
            'tahun' => 2026,
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();
        $this->assertStringContainsString('>Lap</th>', $html);
        $this->assertStringContainsString('07 September 2026 • Siswa E • Laporan: OK', $html);
    }

    public function test_filter_mingguan_dan_periode_tetap_berfungsi(): void
    {
        $siswa = $this->makeSiswa('siswa.d', 'Siswa D', 'INT0034');
        $this->buatLaporan($siswa, '2026-09-08', Laporan::JENIS_SALES);

        $mingguan = $this->actingAs($siswa, 'web')->get(route('rekap.siswa', [
            'jenis' => 'mingguan',
            'tanggal' => '2026-09-08',
        ]));
        $mingguan->assertStatus(200);
        $mingguan->assertSee('Rekap Mingguan');

        $periode = $this->actingAs($siswa, 'web')->get(route('rekap.siswa', [
            'jenis' => 'periode',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
        ]));
        $periode->assertStatus(200);
        $periode->assertSee('Rekap Periode');
    }
}
