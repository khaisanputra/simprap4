<?php

namespace Tests\Feature;

use App\Models\Attendance;
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

class AbsenPulangTest extends TestCase
{
    use RefreshDatabase;

    /** Koordinat resmi Lokasi Kantor Pacitan (dari tabel settings). */
    private const KANTOR_LAT = -8.198644745989196;
    private const KANTOR_LNG = 111.11795852827215;

    protected function setUp(): void
    {
        parent::setUp();

        // Waktu server tetap (WIB) supaya seluruh test absensi deterministik.
        Carbon::setTestNow('2026-08-19 07:30:00');

        User::create([
            'username' => 'siswap',
            'email'    => 'siswap@tes.com',
            'nama'     => 'Siswa Pulang',
            'password' => Hash::make('password123'),
            'role'     => 'siswa',
            'status'   => 'Aktif',
        ]);

        Config::set('absensi.latitude', self::KANTOR_LAT);
        Config::set('absensi.longitude', self::KANTOR_LNG);
        Config::set('absensi.radius', 100);
        Config::set('absensi.gps_wajib', true);
        Config::set('absensi.max_accuracy', 100);
        Config::set('absensi.foto_wajib', false);
        Config::set('absensi.max_foto_kb', 3072);

        // Batas maksimal Absen Pulang (23:00 WIB) — sumber settings.
        // Waktu minimal pulang FIXED 17:00 WIB (tidak lagi jam masuk + 9 jam).
        WaktuAbsen::set('jam_pulang_mulai', '17:00');
        WaktuAbsen::set('jam_pulang_selesai', '23:00');
        LokasiKantor::flush();
        WaktuAbsen::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    protected function loginAsSiswa(): User
    {
        $user = User::where('username', 'siswap')->first();

        $this->actingAs($user);
        $this->withSession([
            'user_id'  => $user->id,
            'username' => $user->username,
            'role'     => $user->role,
        ]);

        return $user;
    }

    protected function absenMasukKan(array $ekstra = [])
    {
        $sebelumnya = Carbon::getTestNow();

        // Absen masuk pukul 08:05 WIB (dalam jendela 08:00-08:30).
        // -> Absen Pulang FIXED mulai 17:00 WIB untuk semua siswa.
        Carbon::setTestNow('2026-08-19 08:05:00');
        $this->post(route('absen.masuk.store'), array_merge($this->koordinatDalamRadius(), $ekstra))
            ->assertSessionHas('absen_success');

        Carbon::setTestNow($sebelumnya);
    }

    protected function koordinatDalamRadius(): array
    {
        return [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ];
    }

    /* =========================================================
       ATURAN WAKTU ABSEN PULANG FIXED:
       minimal = 17:00 WIB (tetap), maksimal = 23:00 WIB (inklusif)
       Tidak lagi jam_masuk + 9 jam. Semua siswa sama.
       ========================================================= */

    /**
     * @dataProvider waktuJendelaPulangProvider
     */
    public function test_batas_waktu_absen_pulang(string $waktu, bool $diterima): void
    {
        Carbon::setTestNow('2026-08-19 '.$waktu);
        $this->loginAsSiswa();
        $this->absenMasukKan();

        $response = $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius());

        if ($diterima) {
            $response->assertSessionHas('absen_success');
            $row = Attendance::where('nama', 'Siswa Pulang')->first();
            $this->assertNotNull($row->jam_pulang);
            $this->assertEquals('2026-08-19', $row->tgl_pulang->format('Y-m-d'));
            $this->assertEquals(substr($waktu, 0, 8), $row->jam_pulang);
        } else {
            $response->assertSessionHas('absen_error');
            $this->assertNull(Attendance::where('nama', 'Siswa Pulang')->value('jam_pulang'));
        }
    }

    public static function waktuJendelaPulangProvider(): array
    {
        // FIXED 17:00 - 23:00 WIB untuk semua siswa.
        return [
            '16:59:59 ditolak'    => ['16:59:59', false],
            '17:00:00 diterima'   => ['17:00:00', true],
            '17:30:00 diterima'   => ['17:30:00', true],
            '18:00:00 diterima'   => ['18:00:00', true],
            '20:00:00 diterima'   => ['20:00:00', true],
            '22:00:00 diterima'   => ['22:00:00', true],
            '22:59:00 diterima'   => ['22:59:00', true],
            '23:00:00 diterima'   => ['23:00:00', true],
            '23:00:01 ditolak'    => ['23:00:01', false],
            '12:00:00 ditolak'    => ['12:00:00', false],
            '16:30:00 ditolak (dulu diterima)' => ['16:30:00', false],
        ];
    }

    public function test_absen_pulang_sebelum_jam_masuk_plus_9jam_pesan_belum_dibuka(): void
    {
        Carbon::setTestNow('2026-08-19 16:59:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();

        $response = $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius());

        $response->assertSessionHas(
            'absen_error',
            'Absen Pulang belum dapat dilakukan. Anda dapat melakukan Absen Pulang mulai pukul 17:00 WIB.'
        );
        $this->assertNull(Attendance::where('nama', 'Siswa Pulang')->value('jam_pulang'));
    }

    public function test_absen_pulang_setelah_2300_pesan_sudah_ditutup(): void
    {
        Carbon::setTestNow('2026-08-19 23:01:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();

        $response = $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius());

        $response->assertSessionHas(
            'absen_error',
            'Absen Pulang sudah ditutup. Batas waktu Absen Pulang adalah pukul 23:00 WIB.'
        );
        $this->assertNull(Attendance::where('nama', 'Siswa Pulang')->value('jam_pulang'));
    }

    public function test_absen_pulang_menolak_parameter_jam_dari_frontend(): void
    {
        // Server WIB sudah 23:30 (jendela tutup), meskipun user mengirim
        // parameter manipulasi (jam dari HP) pada request.
        Carbon::setTestNow('2026-08-19 23:30:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();

        $response = $this->post(route('absen.pulang.store'), array_merge(
            $this->koordinatDalamRadius(),
            ['jam_hp' => '17:00:00', 'tanggal' => '2026-08-19']
        ));

        $response->assertSessionHas(
            'absen_error',
            'Absen Pulang sudah ditutup. Batas waktu Absen Pulang adalah pukul 23:00 WIB.'
        );
        $this->assertNull(Attendance::where('nama', 'Siswa Pulang')->value('jam_pulang'));
    }

    /* =========================================================
       ATURAN BARU FIXED: WAKTU PULANG = 17:00 WIB untuk semua siswa
       Tidak lagi jam_masuk + jam. Semua jam masuk menghasilkan 17:00.
       Sebelum 17:00 DITOLAK; tepat 17:00 DITERIMA; setelah 17:00 DITERIMA.
       Maksimal tetap 23:00 WIB.
       Record dibuat langsung (jam_masuk apapun) agar diuji tanpa
       terikat jendela Absen Masuk 08:00-08:30.
       ========================================================= */

    public static function sembilanJamProvider(): array
    {
        return [
            '07:00 -> 17:00' => ['07:00:00', '17:00'],
            '07:30 -> 17:00' => ['07:30:00', '17:00'],
            '08:00 -> 17:00' => ['08:00:00', '17:00'],
            '08:10 -> 17:00' => ['08:10:00', '17:00'],
            '08:30 -> 17:00' => ['08:30:00', '17:00'],
            '09:00 -> 17:00' => ['09:00:00', '17:00'],
        ];
    }

    protected function absenMasukLangsung(User $user, string $jamMasuk): void
    {
        Attendance::create([
            'user_id'   => $user->id,
            'nama'      => $user->nama,
            'status'    => 'Masuk',
            'tgl_masuk' => '2026-08-19',
            'jam_masuk' => $jamMasuk,
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);
    }

    /**
     * @dataProvider sembilanJamProvider
     */
    public function test_pulang_mulai_tepat_9_jam_setelah_jam_masuk(string $jamMasuk, string $mulaiPulang): void
    {
        $this->assertEquals('17:00', WaktuAbsen::pulangMulai($jamMasuk));
        $this->assertEquals($mulaiPulang, WaktuAbsen::pulangMulai($jamMasuk));
    }

    /**
     * @dataProvider sembilanJamProvider
     */
    public function test_absen_pulang_sebelum_9_jam_ditolak(string $jamMasuk, string $mulaiPulang): void
    {
        $waktuCoba = Carbon::createFromFormat('H:i:s', $mulaiPulang.':00')->subSecond()->format('Y-m-d H:i:s');
        Carbon::setTestNow($waktuCoba);

        $user = $this->loginAsSiswa();
        $this->absenMasukLangsung($user, $jamMasuk);

        $response = $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius());

        $response->assertSessionHas(
            'absen_error',
            'Absen Pulang belum dapat dilakukan. Anda dapat melakukan Absen Pulang mulai pukul '.$mulaiPulang.' WIB.'
        );
        $this->assertNull(Attendance::where('nama', 'Siswa Pulang')->value('jam_pulang'));
    }

    /**
     * @dataProvider sembilanJamProvider
     */
    public function test_absen_pulang_tepat_9_jam_diterima(string $jamMasuk, string $mulaiPulang): void
    {
        Carbon::setTestNow('2026-08-19 '.$mulaiPulang.':00');

        $user = $this->loginAsSiswa();
        $this->absenMasukLangsung($user, $jamMasuk);

        $response = $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius());

        $response->assertSessionHas('absen_success');
        $this->assertNotNull(Attendance::where('nama', 'Siswa Pulang')->value('jam_pulang'));
    }

    /**
     * @dataProvider sembilanJamProvider
     */
    public function test_absen_pulang_setelah_9_jam_diterima(string $jamMasuk, string $mulaiPulang): void
    {
        $waktuCoba = Carbon::createFromFormat('H:i:s', $mulaiPulang.':00')->addHour()->format('Y-m-d H:i:s');
        Carbon::setTestNow($waktuCoba);

        $user = $this->loginAsSiswa();
        $this->absenMasukLangsung($user, $jamMasuk);

        $response = $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius());

        $response->assertSessionHas('absen_success');
        $this->assertNotNull(Attendance::where('nama', 'Siswa Pulang')->value('jam_pulang'));
    }

    /**
     * Dengan aturan FIXED 17:00, jam masuk sore (15:00) tetap bisa pulang
     * mulai 17:00 pada hari yang sama (tidak lagi melewati tengah malam).
     */
    public function test_absen_pulang_jam_masuk_sore_9_jam_melewati_2300_tetap_ditolak(): void
    {
        Carbon::setTestNow('2026-08-19 17:30:00');
        $user = $this->loginAsSiswa();
        $this->absenMasukLangsung($user, '15:00:00');

        // FIXED 17:00 -> 17:30 sudah buka, harus berhasil.
        $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius())
            ->assertSessionHas('absen_success');

        $this->assertNotNull(Attendance::where('nama', 'Siswa Pulang')->value('jam_pulang'));
    }

    /* =========================================================
       PERSYARATAN ABSEN PULANG
       ========================================================= */

    public function test_absen_pulang_tanpa_absen_masuk_ditolak(): void
    {
        Carbon::setTestNow('2026-08-19 18:00:00');
        $this->loginAsSiswa();

        $response = $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius());

        $response->assertSessionHas('absen_error', 'Anda belum melakukan absen masuk hari ini.');
        $this->assertDatabaseMissing('attendances', ['nama' => 'Siswa Pulang']);
    }

    public function test_absen_pulang_kedua_kali_ditolak(): void
    {
        Carbon::setTestNow('2026-08-19 17:30:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();

        $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius())
            ->assertSessionHas('absen_success');

        $response = $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius());

        $response->assertSessionHas('absen_error', 'Anda sudah melakukan absen pulang hari ini.');
        $this->assertEquals(1, Attendance::where('nama', 'Siswa Pulang')->count());
    }

    public function test_absen_pulang_di_luar_radius_ditolak(): void
    {
        Carbon::setTestNow('2026-08-19 18:00:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();

        // ~222 m utara kantor -> di luar Radius 100 m.
        $response = $this->post(route('absen.pulang.store'), [
            'latitude'  => self::KANTOR_LAT + 0.002,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ]);

        $response->assertSessionHas('absen_error');
        $this->assertStringContainsString('di luar Radius Kantor', session('absen_error'));
        $this->assertNull(Attendance::where('nama', 'Siswa Pulang')->value('jam_pulang'));
    }

    public function test_absen_pulang_gps_kosong_ditolak(): void
    {
        Carbon::setTestNow('2026-08-19 18:00:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();

        $response = $this->post(route('absen.pulang.store'), [
            'latitude'  => '',
            'longitude' => '',
            'accuracy'  => '',
        ]);

        $response->assertSessionHasErrors('latitude');
        $this->assertNull(Attendance::where('nama', 'Siswa Pulang')->value('jam_pulang'));
    }

    public function test_absen_pulang_akurasi_gps_rendah_ditolak(): void
    {
        Carbon::setTestNow('2026-08-19 18:00:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();

        $response = $this->post(route('absen.pulang.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 999,
        ]);

        $response->assertSessionHas('absen_error');
        $this->assertStringContainsString('Akurasi GPS terlalu rendah', session('absen_error'));
        $this->assertNull(Attendance::where('nama', 'Siswa Pulang')->value('jam_pulang'));
    }

    public function test_absen_pulang_wajib_foto_ketika_foto_wajib(): void
    {
        Config::set('absensi.foto_wajib', true);
        Storage::fake('public');
        Carbon::setTestNow('2026-08-19 18:00:00');
        $this->loginAsSiswa();
        $this->absenMasukKan(['foto' => UploadedFile::fake()->image('masuk.jpg', 80, 80)]);

        $response = $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius());

        $response->assertSessionHas('absen_error', 'Foto absen pulang wajib diambil sebelum melakukan absen pulang.');
        $this->assertNull(Attendance::where('nama', 'Siswa Pulang')->value('jam_pulang'));
    }

    public function test_absen_pulang_dengan_foto_tersimpan_terpisah(): void
    {
        Config::set('absensi.foto_wajib', true);
        Storage::fake('public');
        Carbon::setTestNow('2026-08-19 18:00:00');
        $this->loginAsSiswa();

        // Catat absen masuk beserta foto masuknya.
        $this->absenMasukKan(['foto' => UploadedFile::fake()->image('masuk.jpg', 80, 80)]);
        $fotoMasuk = Attendance::where('nama', 'Siswa Pulang')->value('foto');
        $this->assertNotNull($fotoMasuk);

        // Foto pulang dikirim bersama request pulang.
        $response = $this->post(route('absen.pulang.store'), array_merge(
            $this->koordinatDalamRadius(),
            ['foto' => UploadedFile::fake()->image('pulang.jpg', 80, 80)]
        ));

        $response->assertSessionHas('absen_success');

        $row = Attendance::where('nama', 'Siswa Pulang')->first();
        $this->assertNotNull($row->foto_pulang);
        $this->assertStringStartsWith('absen/foto-pulang/', $row->foto_pulang);
        $this->assertEquals($fotoMasuk, $row->foto, 'Foto Absen Masuk tidak boleh tertimpa.');
        $this->assertNotEquals($row->foto, $row->foto_pulang, 'Foto pulang harus berbeda dari foto masuk.');
        Storage::disk('public')->assertExists($row->foto_pulang);
        Storage::disk('public')->assertExists($row->foto);

        Storage::disk('public')->delete([$row->foto, $row->foto_pulang]);
    }

    public function test_absen_pulang_menyimpan_snapshot_radius(): void
    {
        Carbon::setTestNow('2026-08-19 17:30:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();

        // Radius saat absen = 100 m.
        $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius())
            ->assertSessionHas('absen_success');

        $row = Attendance::where('nama', 'Siswa Pulang')->first();
        $this->assertEquals(100, (int) $row->radius_kantor_saat_pulang);

        // Admin mengubah radius menjadi 200 — snapshot lama tidak berubah.
        LokasiKantor::set('lokasi_kantor_radius', 200);
        $row->refresh();
        $this->assertEquals(100, (int) $row->radius_kantor_saat_pulang,
            'Histori Absen Pulang lama harus menampilkan radius saat absen (snapshot).');
    }

    public function test_absen_pulang_tidak_mengubah_data_absen_masuk(): void
    {
        Carbon::setTestNow('2026-08-19 17:30:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();

        $sebelum = Attendance::where('nama', 'Siswa Pulang')->first(['jam_masuk', 'tgl_masuk', 'latitude', 'longitude', 'foto', 'jarak', 'radius_kantor_saat_absen'])->toArray();

        $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius())
            ->assertSessionHas('absen_success');

        $sesudah = Attendance::where('nama', 'Siswa Pulang')->first(['jam_masuk', 'tgl_masuk', 'latitude', 'longitude', 'foto', 'jarak', 'radius_kantor_saat_absen'])->toArray();
        $this->assertEquals($sebelum, $sesudah, 'Data Absen Masuk tidak boleh berubah saat Absen Pulang.');
    }

    public function test_record_attendance_tetap_satu(): void
    {
        Carbon::setTestNow('2026-08-19 17:30:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();
        $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius())
            ->assertSessionHas('absen_success');

        $this->assertEquals(1, Attendance::where('nama', 'Siswa Pulang')->count());
    }

    /* =========================================================
       HALAMAN ABSEN PULANG
       ========================================================= */

    public function test_halaman_pulang_menampilkan_kamera_gps_kompas_dan_waktu(): void
    {
        Carbon::setTestNow('2026-08-19 17:05:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();

        $response = $this->get(route('absen.pulang'));

        $response->assertOk()
            ->assertSee('Absen Pulang sudah dibuka.')
            ->assertSee('data-waktu-status="buka"', false)
            ->assertSee('17:00 - 23:00', false)
            ->assertSee('Jam Masuk:')
            ->assertSee('08:05 WIB')
            ->assertSee('Jam Pulang')
            ->assertSee('Absen Pulang tersedia:')
            ->assertSee('17:00 WIB')
            ->assertSee('cameraVideo', false)
            ->assertSee('btnAmbilFoto', false)
            ->assertSee('latField', false)
            ->assertSee('lngField', false)
            ->assertSee('accuracyField', false)
            ->assertSee('compassNeedle', false)
            ->assertSee('Waktu Server (WIB)')
            ->assertSee('Waktu resmi dari server (Asia/Jakarta)');
    }

    public function test_halaman_pulang_sebelum_9_jam_menampilkan_belum_dibuka(): void
    {
        Carbon::setTestNow('2026-08-19 12:00:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();

        $this->get(route('absen.pulang'))
            ->assertSee('Absen Pulang belum dibuka.')
            ->assertSee('data-waktu-status="belum"', false)
            ->assertSee('Anda dapat melakukan Absen Pulang mulai pukul 17:00 WIB.');
    }

    public function test_halaman_pulang_setelah_2300_menampilkan_ditutup(): void
    {
        Carbon::setTestNow('2026-08-19 23:30:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();

        $this->get(route('absen.pulang'))
            ->assertSee('Absen Pulang sudah ditutup.')
            ->assertSee('data-waktu-status="tutup"', false)
            ->assertSee('Tombol absen dinonaktifkan karena sudah melewati pukul 23:00 WIB.');
    }

    public function test_halaman_pulang_menampilkan_belum_absen_masuk(): void
    {
        $this->loginAsSiswa();

        $this->get(route('absen.pulang'))
            ->assertSee('Anda belum melakukan Absen Masuk hari ini')
            ->assertSee('data-boleh-absen="0"', false);
    }

    public function test_halaman_pulang_setelah_selesai_menampilkan_done(): void
    {
        Carbon::setTestNow('2026-08-19 17:30:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();
        $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius());

        $this->get(route('absen.pulang'))
            ->assertSee('Anda sudah melakukan absen pulang hari ini')
            ->assertSee('Jam pulang tercatat');
    }

    /* =========================================================
       DASHBOARD & LAPORAN
       ========================================================= */

    public function test_dashboard_dan_laporan_menampilkan_data_pulang(): void
    {
        Carbon::setTestNow('2026-08-19 17:30:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();
        $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius())
            ->assertSessionHas('absen_success');

        // Dashboard siswa sekarang tidak menampilkan tabel — hanya stat card privat.
        // Pastikan jam pulang tampil di card "Absen Pulang Hari Ini" dan tabel admin tidak bocor.
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('17:30:00')
            ->assertSee('Sudah Absen')
            ->assertDontSee('Aktivitas Absensi Hari Ini');

        // Laporan menampilkan form dan daftar (sesuai implementasi saat ini).
        $this->get(route('laporan.index'))
            ->assertOk()
            ->assertSee('Laporan')
            ->assertDontSee('Aktivitas Absensi Hari Ini');
    }

    public function test_dashboard_statistik_pulang_terhitung(): void
    {
        Carbon::setTestNow('2026-08-19 17:30:00');
        $this->loginAsSiswa();
        $this->absenMasukKan();
        $this->post(route('absen.pulang.store'), $this->koordinatDalamRadius())
            ->assertSessionHas('absen_success');

        $this->get(route('dashboard'))->assertOk();

        $this->assertEquals(
            1,
            Attendance::query()
                ->padaTanggal('2026-08-19')
                ->whereNotNull('tgl_pulang')
                ->whereNotNull('jam_pulang')
                ->count()
        );

        // Aktivitas absensi diperbarui: status record Pulang.
        $row = Attendance::where('nama', 'Siswa Pulang')->first();
        $this->assertEquals('Pulang', $row->status);
    }

    public function test_admin_dapat_mengubah_batas_akhir_absen_pulang(): void
    {
        $admin = User::create([
            'username' => 'adminp',
            'email'    => 'adminp@tes.com',
            'nama'     => 'Admin Pulang',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
            'status'   => 'Aktif',
        ]);

        $this->actingAs($admin);
        $this->withSession([
            'user_id'  => $admin->id,
            'username' => $admin->username,
            'role'     => $admin->role,
        ]);

        // Batas maksimal Absen Pulang diubah dari 23:00 menjadi 22:00 (mulai tetap 17:00).
        $response = $this->post(route('lokasi-kantor.update'), [
            'nama_kantor'   => 'PT Sandya Network',
            'alamat_kantor' => 'Jl. Jend. Sudirman No.3, Pacitan',
            'latitude'      => '-8.198644745989196',
            'longitude'     => '111.11795852827215',
            'radius_kantor' => '100',
            'jam_mulai'     => '07:00',
            'jam_selesai'   => '08:30',
            'jam_pulang_mulai' => '17:00',
            'jam_pulang_selesai' => '22:00',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('22:00', WaktuAbsen::selesai('pulang'));
        $this->assertEquals('17:00', WaktuAbsen::mulai('pulang'));
    }

    public function test_halaman_pengaturan_lokasi_menampilkan_batas_absen_pulang(): void
    {
        $admin = User::create([
            'username' => 'adminp2',
            'email'    => 'adminp2@tes.com',
            'nama'     => 'Admin Dua',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
            'status'   => 'Aktif',
        ]);

        $this->actingAs($admin);
        $this->withSession([
            'user_id'  => $admin->id,
            'username' => $admin->username,
            'role'     => $admin->role,
        ]);

        $this->get(route('lokasi-kantor.index'))
            ->assertOk()
            ->assertSee('Jam Pulang Mulai (WIB)')
            ->assertSee('Jam Pulang Selesai (WIB)');
    }
}