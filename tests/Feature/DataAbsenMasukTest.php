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

class DataAbsenMasukTest extends TestCase
{
    use RefreshDatabase;

    /** Koordinat resmi Lokasi Kantor Pacitan (dari tabel settings). */
    private const KANTOR_LAT = -8.198644745989196;
    private const KANTOR_LNG = 111.11795852827215;

    protected function setUp(): void
    {
        parent::setUp();

        // Waktu server tetap (WIB) supaya seluruh test deterministik.
        // Jendela absen masuk baru 08:00-08:30 WIB.
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

        Config::set('absensi.latitude', self::KANTOR_LAT);
        Config::set('absensi.longitude', self::KANTOR_LNG);
        Config::set('absensi.radius', 100);
        Config::set('absensi.gps_wajib', true);
        Config::set('absensi.max_accuracy', 100);
        Config::set('absensi.foto_wajib', false);
        Config::set('absensi.max_foto_kb', 3072);

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

    protected function buatAbsensi(
        string $nama,
        string $tgl,
        string $jam,
        ?float $lat = null,
        ?float $lng = null,
        ?float $accuracy = null,
        ?float $jarak = null,
        ?float $radius = null
    ): Attendance {
        return Attendance::create([
            'user_id'                  => null,
            'nama'                     => $nama,
            'status'                   => 'Masuk',
            'tgl_masuk'                => $tgl,
            'jam_masuk'                => $jam,
            'latitude'                 => $lat,
            'longitude'                => $lng,
            'accuracy'                 => $accuracy,
            'jarak'                    => $jarak,
            'radius_kantor_saat_absen' => $radius,
        ]);
    }

    public function test_route_data_absen_masuk_hanya_untuk_admin(): void
    {
        $this->loginAsAdmin();

        $this->get(route('data-absen-masuk.index'))->assertOk();
    }

    public function test_siswa_tidak_bisa_mengakses_data_absen_masuk(): void
    {
        $this->loginAsSiswa();

        $this->get(route('data-absen-masuk.index'))->assertForbidden();
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get(route('data-absen-masuk.index'))->assertRedirect(route('login'));
    }

    public function test_hanya_absensi_yang_berhasil_ditampilkan(): void
    {
        $this->loginAsAdmin();

        $this->buatAbsensi('Budi Sudah Masuk', '2026-08-18', '07:32:00', self::KANTOR_LAT, self::KANTOR_LNG, 8, 5.5, 100);
        Attendance::create([
            'nama'      => 'Andi Belum Absen',
            'status'    => 'Masuk',
            'tgl_masuk' => null,
            'jam_masuk' => null,
        ]);

        $response = $this->get(route('data-absen-masuk.index'));

        $response->assertOk()
            ->assertSee('Budi Sudah Masuk')
            ->assertDontSee('Andi Belum Absen');
    }

    public function test_data_terbaru_berada_di_atas(): void
    {
        $this->loginAsAdmin();

        $this->buatAbsensi('Citra Kemarin', '2026-08-17', '08:00:00', self::KANTOR_LAT, self::KANTOR_LNG);
        $this->buatAbsensi('Budi Pagi', '2026-08-18', '07:30:00', self::KANTOR_LAT, self::KANTOR_LNG);
        $this->buatAbsensi('Andi Terbaru', '2026-08-18', '08:00:00', self::KANTOR_LAT, self::KANTOR_LNG);

        $response = $this->get(route('data-absen-masuk.index'));

        $response->assertSeeInOrder(['Andi Terbaru', 'Budi Pagi', 'Citra Kemarin']);
    }

    public function test_filter_tanggal_bekerja(): void
    {
        $this->loginAsAdmin();

        $this->buatAbsensi('Siswa Tanggal 17', '2026-08-17', '07:40:00', self::KANTOR_LAT, self::KANTOR_LNG);
        $this->buatAbsensi('Siswa Tanggal 18', '2026-08-18', '07:45:00', self::KANTOR_LAT, self::KANTOR_LNG);

        $response = $this->get(route('data-absen-masuk.index', ['tanggal' => '2026-08-17']));

        $response->assertOk()
            ->assertSee('Siswa Tanggal 17')
            ->assertDontSee('Siswa Tanggal 18');
    }

    public function test_pencarian_nama_bekerja(): void
    {
        $this->loginAsAdmin();

        $this->buatAbsensi('Budi Santoso', '2026-08-18', '07:30:00', self::KANTOR_LAT, self::KANTOR_LNG);
        $this->buatAbsensi('Andi Wijaya', '2026-08-18', '07:31:00', self::KANTOR_LAT, self::KANTOR_LNG);

        $response = $this->get(route('data-absen-masuk.index', ['cari' => 'Budi']));

        $response->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Andi Wijaya');
    }

    public function test_filter_status_lokasi_valid(): void
    {
        $this->loginAsAdmin();

        $this->buatAbsensi('Siswa Dalam Radius', '2026-08-18', '07:30:00', self::KANTOR_LAT, self::KANTOR_LNG, 8, 25, 100);
        $this->buatAbsensi('Siswa Luar Radius', '2026-08-18', '07:35:00', self::KANTOR_LAT, 111.14, 8, 250, 100);
        $this->buatAbsensi('Siswa Tanpa GPS', '2026-08-18', '07:40:00');

        $response = $this->get(route('data-absen-masuk.index', ['status_lokasi' => 'valid']));

        $response->assertOk()
            ->assertSee('Siswa Dalam Radius')
            ->assertDontSee('Siswa Luar Radius')
            ->assertDontSee('Siswa Tanpa GPS');
    }

    public function test_filter_status_lokasi_tidak_valid(): void
    {
        $this->loginAsAdmin();

        $this->buatAbsensi('Siswa Dalam Radius', '2026-08-18', '07:30:00', self::KANTOR_LAT, self::KANTOR_LNG, 8, 25, 100);
        $this->buatAbsensi('Siswa Luar Radius', '2026-08-18', '07:35:00', self::KANTOR_LAT, 111.14, 8, 250, 100);

        $response = $this->get(route('data-absen-masuk.index', ['status_lokasi' => 'tidak_valid']));

        $response->assertOk()
            ->assertSee('Siswa Luar Radius')
            ->assertDontSee('Siswa Dalam Radius');
    }

    public function test_filter_status_lokasi_memakai_fallback_radius_data_lama(): void
    {
        $this->loginAsAdmin();

        // Data lama sebelum ada snapshot radius: jarak 50 m <= radius default 100 m.
        $this->buatAbsensi('Siswa Lama Valid', '2026-08-18', '07:30:00', self::KANTOR_LAT, self::KANTOR_LNG, 8, 50, null);

        $response = $this->get(route('data-absen-masuk.index', ['status_lokasi' => 'valid']));

        $response->assertOk()
            ->assertSee('Siswa Lama Valid');
    }

    public function test_pagination_bekerja(): void
    {
        $this->loginAsAdmin();

        for ($i = 1; $i <= 12; $i++) {
            $jam = '07:00:'.str_pad((string) ($i * 2), 2, '0', STR_PAD_LEFT);
            $this->buatAbsensi('Siswa Pagi '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), '2026-08-18', $jam, self::KANTOR_LAT, self::KANTOR_LNG);
        }

        $halaman1 = $this->get(route('data-absen-masuk.index'));
        $halaman1->assertOk()
            ->assertSee('Siswa Pagi 12')
            ->assertDontSee('Siswa Pagi 01');

        $halaman2 = $this->get(route('data-absen-masuk.index', ['page' => 2]));
        $halaman2->assertOk()
            ->assertSee('Siswa Pagi 01')
            ->assertDontSee('Siswa Pagi 12');
    }

    public function test_radius_snapshot_tersimpan_dan_tidak_ikut_berubah(): void
    {
        $this->loginAsSiswa();
        LokasiKantor::set('lokasi_kantor_radius', 100);

        // Absen Masuk tanggal 18 dengan Radius Kantor 100 m.
        $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 8,
        ])->assertSessionHas('absen_success');

        $barisLama = Attendance::where('nama', 'Siswa Absen')->latest('id')->first();
        $this->assertNotNull($barisLama);
        $this->assertEquals(100, $barisLama->radius_kantor_saat_absen);
        $this->assertNotNull($barisLama->jarak);

        // Admin mengubah Radius Kantor menjadi 250 m pada tanggal 20.
        Carbon::setTestNow('2026-08-20 08:15:00');
        LokasiKantor::set('lokasi_kantor_radius', 250);
        $this->assertTrue(Setting::where('key', 'lokasi_kantor_radius')->value('value') == '250');

        // Absen Masuk tanggal 20 menggunakan Radius baru.
        $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 8,
        ])->assertSessionHas('absen_success');

        $barisBaru = Attendance::where('nama', 'Siswa Absen')->whereDate('tgl_masuk', '2026-08-20')->first();
        $this->assertNotNull($barisBaru);
        $this->assertEquals(250, $barisBaru->radius_kantor_saat_absen);

        // Histori lama TETAP menampilkan 100, tidak ikut berubah.
        $barisLama->refresh();
        $this->assertEquals(100, $barisLama->radius_kantor_saat_absen);

        // Data lama juga harus menampilkan jarak & radius aslinya di halaman.
        $this->loginAsAdmin();
        $this->get(route('data-absen-masuk.index', ['tanggal' => '2026-08-18']))
            ->assertOk()
            ->assertSee('Siswa Absen');
    }

    public function test_jam_masuk_wib_dari_server(): void
    {
        $this->loginAsSiswa();

        $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ])->assertSessionHas('absen_success');

        $row = Attendance::where('nama', 'Siswa Absen')->first();
        $this->assertEquals('2026-08-18', $row->tgl_masuk->format('Y-m-d'));
        $this->assertEquals('08:15:00', $row->jam_masuk);
        $this->assertEqualsWithDelta(self::KANTOR_LAT, $row->latitude, 1e-9);

        $this->loginAsAdmin();
        $this->get(route('data-absen-masuk.index'))
            ->assertSee('08:15:00')
            ->assertSee('18-08-2026');
    }

    public function test_absen_pulang_tetap_menggunakan_record_yang_sama(): void
    {
        $this->loginAsSiswa();

        $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ])->assertSessionHas('absen_success');

        Carbon::setTestNow('2026-08-18 18:05:00');
        $this->post(route('absen.pulang.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
        ])->assertSessionHas('absen_success');

        // Tetap 1 record: masuk & pulang pada record yang sama.
        $this->assertEquals(1, Attendance::where('nama', 'Siswa Absen')->count());
        $row = Attendance::where('nama', 'Siswa Absen')->first();
        $this->assertEquals('08:15:00', $row->jam_masuk);
        $this->assertEquals('18:05:00', $row->jam_pulang);

        // Data Absen Masuk tetap menampilkan record tersebut.
        $this->loginAsAdmin();
        $this->get(route('data-absen-masuk.index'))
            ->assertOk()
            ->assertSee('Siswa Absen')
            ->assertSee('08:15:00');
    }

    public function test_foto_tampil_sebagai_thumbnail_dan_foto_kosong_menampilkan_pesan(): void
    {
        $this->loginAsSiswa();

        $this->post(route('absen.masuk.store'), [
            'latitude'  => self::KANTOR_LAT,
            'longitude' => self::KANTOR_LNG,
            'accuracy'  => 15,
            'foto'      => UploadedFile::fake()->image('selfie.jpg', 80, 80),
        ])->assertSessionHas('absen_success');

        $pathFoto = Attendance::where('nama', 'Siswa Absen')->value('foto');
        $this->assertNotNull($pathFoto);
        Storage::disk('public')->assertExists($pathFoto);

        $this->buatAbsensi('Siswa Tanpa Foto', '2026-08-18', '08:00:00', self::KANTOR_LAT, self::KANTOR_LNG);

        $this->loginAsAdmin();
        $response = $this->get(route('data-absen-masuk.index'));

        // Thumbnail memakai path yang tersimpan di storage publik.
        $response->assertSee('storage/'.$pathFoto, false)
            ->assertSee('Foto tidak tersedia');
    }

    public function test_hari_ini_berdasarkan_tanggal_wib(): void
    {
        $this->loginAsAdmin();

        $this->buatAbsensi('Siswa Hari Ini', '2026-08-18', '07:30:00', self::KANTOR_LAT, self::KANTOR_LNG);
        $this->buatAbsensi('Siswa Kemarin', '2026-08-17', '07:30:00', self::KANTOR_LAT, self::KANTOR_LNG);

        $response = $this->get(route('data-absen-masuk.index'));
        $response->assertOk()
            ->assertSee('data-jumlah-hari-ini="1"', false)
            ->assertSee('tanggal=2026-08-18', false);

        // Ketika tanggal WIB server berganti, "Hari Ini" ikut berganti.
        Carbon::setTestNow('2026-08-19 07:30:00');

        $response = $this->get(route('data-absen-masuk.index'));
        $response->assertOk()
            ->assertSee('data-jumlah-hari-ini="0"', false)
            ->assertSee('tanggal=2026-08-19', false);
    }

    public function test_data_detail_tersedia_untuk_modal(): void
    {
        $this->loginAsAdmin();

        $this->buatAbsensi('Budi Detail', '2026-08-18', '07:32:15', self::KANTOR_LAT, self::KANTOR_LNG, 8, 42.35, 100);

        $response = $this->get(route('data-absen-masuk.index'));

        $response->assertOk()
            ->assertSee('window.ABSEN_DETAILS')
            ->assertSee('42.35')
            ->assertSee('Lihat Detail');
    }

    public function test_nama_menampilkan_nama_terbaru_dari_tabel_users(): void
    {
        $this->loginAsAdmin();

        $user = User::create([
            'username' => 'namabaru',
            'nama'     => 'Siswa Nama Baru',
            'password' => 'password123',
            'role'     => 'siswa',
            'status'   => 'Aktif',
        ]);

        // Record absensi lama masih menyimpan nama lama (snapshot saat absen),
        // tetapi terhubung ke user lewat user_id.
        Attendance::create([
            'user_id'   => $user->id,
            'nama'      => 'Siswa Nama Lama',
            'status'    => 'Masuk',
            'tgl_masuk' => '2026-08-18',
            'jam_masuk' => '07:45:00',
        ]);

        // Nama pengguna diubah setelah absen dilakukan.
        $user->update(['nama' => 'Siswa Nama Terbaru']);

        $response = $this->get(route('data-absen-masuk.index'));
        $response->assertOk()
            ->assertSee('Siswa Nama Terbaru')
            ->assertDontSee('Siswa Nama Lama');

        // Pencarian juga menemukan record lewat nama pengguna saat ini.
        $this->get(route('data-absen-masuk.index', ['cari' => 'Nama Terbaru']))
            ->assertOk()
            ->assertSee('Siswa Nama Terbaru');

        // Jika user dihapus, nama tersimpan (snapshot) tetap tampil.
        $user->delete();

        $this->get(route('data-absen-masuk.index'))
            ->assertOk()
            ->assertSee('Siswa Nama Lama');
    }
}