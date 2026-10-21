<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Setting;
use App\Models\User;
use App\Support\LokasiKantor;
use App\Support\WaktuAbsen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AbsenFlowTest extends TestCase
{
    use RefreshDatabase;

    /** Koordinat resmi Lokasi Kantor Pacitan (dari tabel settings). */
    private const KANTOR_LAT = -8.198644745989196;
    private const KANTOR_LNG = 111.11795852827215;

    protected function setUp(): void
    {
        parent::setUp();

        // Waktu server tetap (WIB) supaya seluruh test absensi deterministik:
        // pukul 08:15 WIB termasuk jendela buka (08:00 - 08:30).
        Carbon::setTestNow('2026-08-18 08:15:00');

        User::create([
            'username' => 'siswaa',
            'email'    => 'siswaa@tes.com',
            'nama'     => 'Siswa Absen',
            'password' => Hash::make('password123'),
            'role'     => 'siswa',
            'status'   => 'Aktif',
        ]);

        User::create([
            'username' => 'adminx',
            'email'    => 'adminx@tes.com',
            'nama'     => 'Admin Tes',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
            'status'   => 'Aktif',
        ]);

        User::create([
            'username' => 'nonaktif',
            'email'    => 'nonaktif@tes.com',
            'nama'     => 'User Nonaktif',
            'password' => Hash::make('password123'),
            'role'     => 'siswa',
            'status'   => 'Nonaktif',
        ]);

        Config::set('absensi.latitude', self::KANTOR_LAT);
        Config::set('absensi.longitude', self::KANTOR_LNG);
        Config::set('absensi.radius', 100);
        Config::set('absensi.gps_wajib', true);
        Config::set('absensi.max_accuracy', 100);
        Config::set('absensi.foto_wajib', false);
        Config::set('absensi.max_foto_kb', 3072);

        // Bersihkan cache statis agar setiap test membaca nilai
        // settings dari database yang di-rollback.
        LokasiKantor::flush();
        WaktuAbsen::flush();
    }

    protected function loginAsSiswa(): User
    {
        $user = User::where('username', 'siswaa')->first();

        $this->actingAs($user);
        $this->withSession([
            'user_id'  => $user->id,
            'username' => $user->username,
            'role'     => $user->role,
        ]);

        return $user;
    }

    protected function loginAsAdmin(): User
    {
        $user = User::where('username', 'adminx')->first();

        $this->actingAs($user);
        $this->withSession([
            'user_id'  => $user->id,
            'username' => $user->username,
            'role'     => $user->role,
        ]);

        return $user;
    }

    public function test_guest_is_redirected_from_absen_masuk(): void
    {
        $this->get(route('absen.masuk'))->assertRedirect(route('login'));
    }

    public function test_absen_masuk_success_saves_to_attendance(): void
    {
        $this->loginAsSiswa();

        $response = $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);

        $response->assertRedirect(route('absen.masuk'));

        $row = Attendance::where('nama', 'Siswa Absen')->first();
        $this->assertNotNull($row);
        $this->assertEquals('Masuk', $row->status);
        $this->assertEquals(now()->toDateString(), \Illuminate\Support\Carbon::parse($row->tgl_masuk)->toDateString());
        $this->assertNotNull($row->jam_masuk);
        $this->assertEqualsWithDelta(self::KANTOR_LAT, $row->latitude, 1e-9);
        $this->assertEqualsWithDelta(self::KANTOR_LNG, $row->longitude, 1e-9);
        $this->assertEquals(15, $row->accuracy);
    }

    public function test_absen_masuk_duplicate_is_rejected(): void
    {
        $this->loginAsSiswa();

        $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);

        $response = $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);

        $response->assertSessionHas('absen_error', 'Anda sudah melakukan absen masuk hari ini.');
        $this->assertEquals(1, Attendance::where('nama', 'Siswa Absen')->count());
    }

    public function test_absen_masuk_outside_radius_is_rejected(): void
    {
        $this->loginAsSiswa();

        $response = $this->post(route('absen.masuk.store'), [
            'latitude'  => -6.0000,
            'longitude' => 107.0000,
            'accuracy'  => 20,
        ]);

        $response->assertSessionHas('absen_error');
        $this->assertDatabaseMissing('attendances', ['nama' => 'Siswa Absen']);
    }

    public function test_absen_masuk_missing_coords_is_rejected(): void
    {
        $this->loginAsSiswa();

        $response = $this->post(route('absen.masuk.store'), [
            'latitude'  => '',
            'longitude' => '',
            'accuracy'  => '',
        ]);

        $response->assertSessionHasErrors('latitude');
        $this->assertDatabaseMissing('attendances', ['nama' => 'Siswa Absen']);
    }

    public function test_absen_masuk_rejects_without_foto_when_wajib(): void
    {
        Config::set('absensi.foto_wajib', true);
        $this->loginAsSiswa();

        $response = $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);

        $response->assertSessionHas('absen_error', 'Foto absen wajib diambil sebelum melakukan absen masuk.');
        $this->assertDatabaseMissing('attendances', ['nama' => 'Siswa Absen']);
    }

    public function test_absen_masuk_with_foto_saves_photo_and_jarak(): void
    {
        Config::set('absensi.foto_wajib', true);
        Storage::fake('public');
        $this->loginAsSiswa();

        $response = $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
            'foto'      => UploadedFile::fake()->image('selfie.jpg', 80, 80),
        ]);

        $response->assertRedirect(route('absen.masuk'));
        $response->assertSessionHas('absen_success');

        $row = Attendance::where('nama', 'Siswa Absen')->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->foto);
        $this->assertStringStartsWith('absen/foto/', $row->foto);
        $this->assertLessThan(1, (float) $row->jarak);
        Storage::disk('public')->assertExists($row->foto);

        Storage::disk('public')->delete($row->foto);
    }

    public function test_absen_masuk_rejects_invalid_foto_file(): void
    {
        $this->loginAsSiswa();

        $response = $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
            'foto'      => UploadedFile::fake()->create('dokumen.txt', 100),
        ]);

        $response->assertSessionHasErrors('foto');
        $this->assertDatabaseMissing('attendances', ['nama' => 'Siswa Absen']);
    }

    public function test_radius_kantor_dari_settings_dipakai_validasi(): void
    {
        $this->loginAsSiswa();

        // Koordinat ~111 meter utara kantor Pacitan (1 derajat lintang ≈ 111 km).
        $latLuar = self::KANTOR_LAT + 0.001;

        LokasiKantor::set('lokasi_kantor_radius', 50);

        $response = $this->post(route('absen.masuk.store'), [
            'latitude'  => $latLuar,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);

        $response->assertSessionHas('absen_error');
        $this->assertDatabaseMissing('attendances', ['nama' => 'Siswa Absen']);

        LokasiKantor::set('lokasi_kantor_radius', 500);

        $response = $this->post(route('absen.masuk.store'), [
            'latitude'  => $latLuar,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);

        $response->assertSessionHas('absen_success');
        $this->assertDatabaseHas('attendances', ['nama' => 'Siswa Absen']);

        $row = Attendance::where('nama', 'Siswa Absen')->first();
        $this->assertGreaterThan(100, (float) $row->jarak);
    }

    public function test_admin_can_update_lokasi_kantor_and_jam_absen(): void
    {
        $this->loginAsAdmin();

        $response = $this->post(route('lokasi-kantor.update'), [
            'nama_kantor'   => 'Kantor Cabang Pacitan',
            'alamat_kantor' => 'Jl. Jend. Sudirman No.3, Pacitan, Jawa Timur 63516',
            'latitude'      => '-8.200000',
            'longitude'     => '111.100000',
            'radius_kantor' => '150',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '09:00',
            'jam_pulang_mulai' => '17:00',
            'jam_pulang_selesai' => '23:00',
        ]);

        $response->assertRedirect(route('lokasi-kantor.index'));
        $response->assertSessionHas('success');

        $this->assertEquals('Kantor Cabang Pacitan', Setting::where('key', 'lokasi_kantor_nama')->value('value'));
        $this->assertEquals('-8.200000', Setting::where('key', 'lokasi_kantor_latitude')->value('value'));
        $this->assertEquals('150', Setting::where('key', 'lokasi_kantor_radius')->value('value'));
        $this->assertEquals('08:00', Setting::where('key', 'jam_absen_mulai')->value('value'));
        $this->assertEquals('09:00', Setting::where('key', 'jam_absen_selesai')->value('value'));

        $lokasi = LokasiKantor::all();
        $this->assertEquals('Kantor Cabang Pacitan', $lokasi['nama']);
        $this->assertEquals(-8.2, $lokasi['latitude']);
        $this->assertEquals(150, $lokasi['radius']);

        $this->assertEquals('08:00', WaktuAbsen::mulai());
        $this->assertEquals('09:00', WaktuAbsen::selesai());
        $this->assertEquals('23:00', WaktuAbsen::selesai('pulang'));
    }

    public function test_jam_absen_tidak_boleh_mulai_lebih_besar_dari_selesai(): void
    {
        $this->loginAsAdmin();

        $response = $this->post(route('lokasi-kantor.update'), [
            'nama_kantor'   => 'Kantor',
            'alamat_kantor' => 'Alamat',
            'latitude'      => '-8.200000',
            'longitude'     => '111.100000',
            'radius_kantor' => '100',
            'jam_mulai'     => '08:30',
            'jam_selesai'   => '07:00',
            'jam_pulang_mulai' => '17:00',
            'jam_pulang_selesai' => '23:00',
        ]);

        $response->assertSessionHasErrors('jam_selesai');
        $this->assertEquals('08:00', Setting::where('key', 'jam_absen_mulai')->value('value'));
    }

    public function test_lokasi_kantor_validation_rejects_invalid_values(): void
    {
        $this->loginAsAdmin();

        $response = $this->post(route('lokasi-kantor.update'), [
            'nama_kantor'   => '',
            'alamat_kantor' => '',
            'latitude'      => '999',
            'longitude'     => 'abc',
            'radius_kantor' => '5',
            'jam_mulai'     => '15:00',
            'jam_selesai'   => '14:00',
        ]);

        $response->assertSessionHasErrors([
            'nama_kantor', 'alamat_kantor', 'latitude', 'longitude', 'radius_kantor', 'jam_selesai',
        ]);
    }

    public function test_siswa_cannot_access_lokasi_kantor_page(): void
    {
        $this->loginAsSiswa();

        $this->get(route('lokasi-kantor.index'))->assertForbidden();
        $this->post(route('lokasi-kantor.update'), [
            'nama_kantor'   => 'Hack',
            'alamat_kantor' => 'x',
            'latitude'      => '0',
            'longitude'     => '0',
            'radius_kantor' => '100',
        ])->assertForbidden();
    }

    public function test_guest_redirected_from_lokasi_kantor_page(): void
    {
        $this->get(route('lokasi-kantor.index'))->assertRedirect(route('login'));
    }

    public function test_absen_pulang_without_masuk_is_rejected(): void
    {
        $this->loginAsSiswa();

        Carbon::setTestNow('2026-08-18 18:00:00');
        $response = $this->post(route('absen.pulang.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);

        $response->assertSessionHas('absen_error', 'Anda belum melakukan absen masuk hari ini.');
        $this->assertDatabaseMissing('attendances', ['nama' => 'Siswa Absen']);
    }

    public function test_absen_pulang_updates_same_record(): void
    {
        $this->loginAsSiswa();

        $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);

        Carbon::setTestNow('2026-08-18 18:00:00');
        $response = $this->post(route('absen.pulang.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);

        $response->assertSessionHas('absen_success');

        $row = Attendance::where('nama', 'Siswa Absen')->first();
        $this->assertNotNull($row->jam_pulang);
        $this->assertEquals(now()->toDateString(), $row->tgl_pulang->format('Y-m-d'));
        $this->assertEquals('Pulang', $row->status);
        $this->assertEqualsWithDelta(self::KANTOR_LAT, $row->latitude_pulang, 1e-9);
        $this->assertLessThan(1, (float) $row->jarak_pulang);
        $this->assertEquals(100, (int) $row->radius_kantor_saat_pulang);

        // Tidak membuat record baru.
        $this->assertEquals(1, Attendance::where('nama', 'Siswa Absen')->count());
    }

    public function test_absen_pulang_duplicate_is_rejected(): void
    {
        $this->loginAsSiswa();

        $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);

        Carbon::setTestNow('2026-08-18 18:00:00');
        $this->post(route('absen.pulang.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);
        $waktuPulang = Attendance::where('nama', 'Siswa Absen')->value('jam_pulang');

        $response = $this->post(route('absen.pulang.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);

        $response->assertSessionHas('absen_error', 'Anda sudah melakukan absen pulang hari ini.');
        $this->assertEquals($waktuPulang, Attendance::where('nama', 'Siswa Absen')->value('jam_pulang'));
    }

    public function test_login_rejected_for_nonaktif_user(): void
    {
        $response = $this->from('/login')->post(route('login.process'), [
            'username' => 'nonaktif',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'Akun Anda berstatus Nonaktif.');
    }

    public function test_nonaktif_session_is_cleared_by_middleware(): void
    {
        $user = User::where('username', 'nonaktif')->first();

        $this->actingAs($user);
        $this->withSession([
            'user_id'  => $user->id,
            'username' => $user->username,
            'role'     => $user->role,
        ]);

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * Batas waktu Absen Masuk (server WIB):
     * 08:00:00 s/d 08:30:00 VALID; di luar itu DITOLAK.
     *
     * @dataProvider waktuJendelaAbsenProvider
     */
    public function test_batas_waktu_absen_masuk(string $waktu, bool $diterima): void
    {
        Carbon::setTestNow('2026-08-18 '.$waktu);
        $this->loginAsSiswa();

        $response = $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);

        if ($diterima) {
            $response->assertSessionHas('absen_success');
            $row = Attendance::where('nama', 'Siswa Absen')->first();
            $this->assertNotNull($row);
            $this->assertEquals('2026-08-18', $row->tgl_masuk->format('Y-m-d'));
            $this->assertEquals(substr($waktu, 0, 8), $row->jam_masuk);
        } else {
            $response->assertSessionHas('absen_error');
            $this->assertDatabaseMissing('attendances', ['nama' => 'Siswa Absen']);
        }
    }

    public static function waktuJendelaAbsenProvider(): array
    {
        return [
            '06:59:59 ditolak'    => ['06:59:59', false],
            '07:00:00 ditolak'    => ['07:00:00', false],
            '07:30:00 ditolak'    => ['07:30:00', false],
            '07:59:59 ditolak'    => ['07:59:59', false],
            '08:00:00 diterima'   => ['08:00:00', true],
            '08:15:00 diterima'   => ['08:15:00', true],
            '08:29:59 diterima'   => ['08:29:59', true],
            '08:30:00 diterima'   => ['08:30:00', true],
            '08:30:01 diterima Telat'    => ['08:30:01', true],
            '09:00:00 diterima Telat'    => ['09:00:00', true],
            '10:00:00 diterima Telat'    => ['10:00:00', true],
        ];
    }

    public function test_jam_hp_user_tidak_dipercaya_setelah_0830(): void
    {
        // Server WIB sudah 09:00 (harusnya Telat), meskipun user mengirim
        // parameter manipulasi (jam dari HP) pada request, tetap Telat sesuai waktu server.
        Carbon::setTestNow('2026-08-18 09:00:00');
        $this->loginAsSiswa();

        $response = $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
            'jam_hp'    => '07:30:00',
            'tanggal'   => '2026-08-18',
        ]);

        $response->assertSessionHas('absen_success');
        $row = Attendance::where('nama', 'Siswa Absen')->first();
        $this->assertNotNull($row);
        $this->assertEquals('Telat', $row->status_kehadiran);
        $this->assertEquals('09:00:00', $row->jam_masuk);
    }

    public function test_absen_ditolak_sebelum_jam_0700(): void
    {
        Carbon::setTestNow('2026-08-18 06:45:00');
        $this->loginAsSiswa();

        $response = $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);

        $response->assertSessionHas('absen_error', 'Absen Masuk belum dibuka. Silakan melakukan absensi mulai pukul 08:00 WIB.');
        $this->assertDatabaseMissing('attendances', ['nama' => 'Siswa Absen']);
    }

    public function test_pergantian_hari_absen_lagi_hari_baru(): void
    {
        Carbon::setTestNow('2026-08-18 08:10:00');
        $this->loginAsSiswa();

        $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);

        // Hari yang sama: absen kedua ditolak (tetap 1 record).
        Carbon::setTestNow('2026-08-18 08:20:00');

        $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ])->assertSessionHas('absen_error', 'Anda sudah melakukan absen masuk hari ini.');

        $this->assertEquals(1, Attendance::where('nama', 'Siswa Absen')->count());

        // Hari WIB berikutnya (00:00 WIB berganti tanggal): bisa absen lagi.
        Carbon::setTestNow('2026-08-19 08:10:00');

        $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ])->assertSessionHas('absen_success');

        $this->assertEquals(2, Attendance::where('nama', 'Siswa Absen')->count());

        $barisHariBaru = Attendance::where('nama', 'Siswa Absen')->latest('id')->first();
        $this->assertEquals('2026-08-19', $barisHariBaru->tgl_masuk->format('Y-m-d'));
    }

    public function test_halaman_absen_masuk_menampilkan_status_waktu(): void
    {
        $this->loginAsSiswa();

        Carbon::setTestNow('2026-08-18 06:45:00');
        $page = $this->get(route('absen.masuk'));
        $page->assertOk()->assertSee('Absen Masuk belum dibuka.')
            ->assertSee('Jam Absen', false)
            ->assertSee('data-waktu-status="belum"', false);

        Carbon::setTestNow('2026-08-18 08:15:00');
        $this->get(route('absen.masuk'))
            ->assertSee('Absen Masuk sedang dibuka.')
            ->assertSee('data-waktu-status="buka"', false);

        Carbon::setTestNow('2026-08-18 08:45:00');
        $this->get(route('absen.masuk'))
            ->assertSee('Telat')
            ->assertSee('data-waktu-status="tutup"', false);
    }
}