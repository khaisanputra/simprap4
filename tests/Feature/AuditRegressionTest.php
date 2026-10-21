<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Laporan;
use App\Models\User;
use App\Support\MonthlyStatisticService;
use App\Support\WaktuAbsen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Carbon::setTestNow('2026-09-08 10:00:00');
        WaktuAbsen::flush();
        \App\Models\Setting::updateOrCreate(['key' => 'jam_pulang_mulai'], ['value' => '17:00']);
        \App\Models\Setting::updateOrCreate(['key' => 'jam_pulang_selesai'], ['value' => '23:00']);
        \App\Models\Setting::updateOrCreate(['key' => 'jam_absen_mulai'], ['value' => '07:00']);
        \App\Models\Setting::updateOrCreate(['key' => 'jam_absen_selesai'], ['value' => '08:30']);
        WaktuAbsen::flush();
    }

    private function makeSiswa($username = 'siswa1', $nama = 'Siswa Satu', $userId = 'G1-001', $createdAt = '2026-08-01'): User
    {
        $u = User::create([
            'username' => $username,
            'email' => $username.'@test.com',
            'nama' => $nama,
            'password' => Hash::make('password123'),
            'role' => 'siswa',
            'status' => 'Aktif',
            'user_id' => $userId,
        ]);
        $u->created_at = Carbon::parse($createdAt);
        $u->updated_at = Carbon::parse($createdAt);
        $u->save();
        return $u->fresh();
    }

    private function makeAdmin($username = 'admin', $nama = 'Admin'): User
    {
        return User::create([
            'username' => $username,
            'email' => $username.'@test.com',
            'nama' => $nama,
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'Aktif',
        ]);
    }

    private function loginAs(User $user): void
    {
        $this->withSession(['user_id' => $user->id, 'username' => $user->username, 'role' => $user->role]);
        $this->actingAs($user);
    }

    private function fakeFoto(): UploadedFile
    {
        return UploadedFile::fake()->image('foto.jpg', 200, 200)->size(100);
    }

    private function buatAbsensiMasuk(User $user, string $status = 'Masuk'): void
    {
        \App\Models\Attendance::create([
            'user_id' => $user->id,
            'nama' => $user->nama,
            'status' => $status,
            'status_kehadiran' => $status,
            'tgl_masuk' => now()->toDateString(),
            'jam_masuk' => '08:00:00',
            'tgl_pulang' => null,
            'jam_pulang' => null,
        ]);
    }

    // 1. Admin tidak boleh absen
    public function test_admin_tidak_bisa_absen_masuk(): void
    {
        $admin = $this->makeAdmin();
        $this->loginAs($admin);
        $response = $this->post(route('absen.masuk.store'), [
            'status_kehadiran' => 'Masuk',
            'latitude' => -8.1986,
            'longitude' => 111.1179,
            'accuracy' => 10,
            'foto' => $this->fakeFoto(),
        ]);
        // Harus ditolak, tidak membuat attendance
        $this->assertTrue($response->status() === 403 || $response->isRedirect());
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_admin_tidak_bisa_absen_pulang(): void
    {
        $admin = $this->makeAdmin();
        $siswa = $this->makeSiswa('siswa2','S2','G1-002');
        // Buat absen masuk untuk siswa agar bisa pulang, tapi admin coba pulang pakai ID siswa? Admin tidak punya absen masuk
        $this->loginAs($admin);
        $response = $this->post(route('absen.pulang.store'), [
            'latitude' => -8.1986,
            'longitude' => 111.1179,
            'accuracy' => 10,
            'foto' => $this->fakeFoto(),
        ]);
        $this->assertTrue($response->status() === 403 || $response->isRedirect());
        $this->assertDatabaseCount('attendances', 0);
    }

    // 4. Alpha logic regression
    public function test_alpha_sebelum_1700_tidak_alpha(): void
    {
        Carbon::setTestNow('2026-09-08 16:30:00'); // Senin sebelum 17:00
        WaktuAbsen::flush();
        $u = $this->makeSiswa('a1','A1','G1-A1');
        $start = Carbon::parse('2026-09-08');
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(0, $data['jumlah_alpha'], 'Sebelum 17:00 tidak Alpha');
        // DataAbsen juga
        $this->assertEquals(0, Attendance::tidakMasukUntukTanggal('2026-09-08', Carbon::parse('2026-09-08 16:30:00'))->count());
    }

    public function test_alpha_1800_tidak_alpha(): void
    {
        Carbon::setTestNow('2026-09-08 18:00:00');
        WaktuAbsen::flush();
        $u = $this->makeSiswa('a2','A2','G1-A2');
        $start = Carbon::parse('2026-09-08');
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(0, $data['jumlah_alpha'], '18:00 tidak Alpha (belum 23:00)');
        $this->assertEquals(0, Attendance::tidakMasukUntukTanggal('2026-09-08', Carbon::parse('2026-09-08 18:00:00'))->count());
    }

    public function test_alpha_2200_tidak_alpha(): void
    {
        Carbon::setTestNow('2026-09-08 22:00:00');
        WaktuAbsen::flush();
        $u = $this->makeSiswa('a3','A3','G1-A3');
        $start = Carbon::parse('2026-09-08');
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(0, $data['jumlah_alpha'], '22:00 tidak Alpha');
    }

    public function test_alpha_setelah_2300_alpha(): void
    {
        Carbon::setTestNow('2026-09-08 23:30:00');
        WaktuAbsen::flush();
        $u = $this->makeSiswa('a4','A4','G1-A4');
        $start = Carbon::parse('2026-09-08');
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(1, $data['jumlah_alpha'], 'Setelah 23:00 Alpha');
        $this->assertEquals(1, Attendance::tidakMasukUntukTanggal('2026-09-08', Carbon::parse('2026-09-08 23:30:00'))->count());
    }

    public function test_alpha_weekend_tidak_alpha(): void
    {
        Carbon::setTestNow('2026-09-06 23:30:00'); // Sabtu
        WaktuAbsen::flush();
        $u = $this->makeSiswa('a5','A5','G1-A5');
        $start = Carbon::parse('2026-09-06');
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(0, $data['jumlah_alpha'], 'Weekend tidak Alpha');
        $this->assertEquals(0, Attendance::tidakMasukUntukTanggal('2026-09-06', Carbon::parse('2026-09-06 23:30:00'))->count());
    }

    public function test_alpha_future_tidak_alpha(): void
    {
        Carbon::setTestNow('2026-09-08 10:00:00');
        WaktuAbsen::flush();
        $u = $this->makeSiswa('a6','A6','G1-A6');
        $start = Carbon::parse('2026-09-10'); // future
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(0, $data['jumlah_alpha'], 'Masa depan tidak Alpha');
    }

    public function test_alpha_sudah_masuk_tidak_alpha(): void
    {
        Carbon::setTestNow('2026-09-08 23:30:00');
        WaktuAbsen::flush();
        $u = $this->makeSiswa('a7','A7','G1-A7');
        Attendance::create([
            'user_id' => $u->id,
            'nama' => $u->nama,
            'status_kehadiran' => 'Masuk',
            'tgl_masuk' => '2026-09-08',
            'jam_masuk' => '08:00:00',
            'jam_pulang' => '17:10:00',
            'tgl_pulang' => '2026-09-08',
        ]);
        $start = Carbon::parse('2026-09-08');
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(0, $data['jumlah_alpha'], 'Sudah Masuk dengan pulang tidak Alpha');
        $this->assertEquals(0, Attendance::tidakMasukUntukTanggal('2026-09-08', Carbon::parse('2026-09-08 23:30:00'))->count());
    }

    public function test_alpha_sudah_masuk_tanpa_pulang_tidak_alpha(): void
    {
        // ATURAN BARU 23:00 FINALISASI: Masuk tanpa Pulang setelah 23:00 => Alpha (HADIR hanya dengan Pulang)
        Carbon::setTestNow('2026-09-08 23:30:00');
        WaktuAbsen::flush();
        $u = $this->makeSiswa('a8','A8','G1-A8');
        Attendance::create([
            'user_id' => $u->id,
            'nama' => $u->nama,
            'status_kehadiran' => 'Masuk',
            'tgl_masuk' => '2026-09-08',
            'jam_masuk' => '08:00:00',
            'jam_pulang' => null,
        ]);
        $start = Carbon::parse('2026-09-08');
        $end = $start->copy();
        $data = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end);
        $this->assertEquals(1, $data['jumlah_alpha'], 'Masuk tanpa Pulang setelah 23:00 harus Alpha (HADIR hanya dengan Pulang)');
        // Sebelum 23:00 harus pending (tidak Alpha)
        Carbon::setTestNow('2026-09-08 20:00:00');
        WaktuAbsen::flush();
        $data2 = MonthlyStatisticService::hitungUntukUserPeriode($u->id, $start, $end, Carbon::parse('2026-09-08 20:00:00'));
        $this->assertEquals(0, $data2['jumlah_alpha'], 'Masuk tanpa Pulang sebelum 23:00 tidak Alpha (sementara)');
    }

    // 6. Sales Collect harus untuk Sales parent saja
    public function test_sales_collect_ditolak_untuk_teknisi(): void
    {
        $siswa = $this->makeSiswa('s9','S9','G1-009');
        $this->loginAs($siswa);
        $this->buatAbsensiMasuk($siswa);
        // Buat laporan Teknisi
        $response = $this->post(route('laporan.store'), [
            'teks_laporan' => 'Laporan Teknisi minimal 10 karakter untuk test',
            'fotos' => [$this->fakeFoto()],
        ]);
        $teknisi = Laporan::where('user_id', $siswa->id)->first();
        $this->assertNotNull($teknisi);
        // Coba collect via Sales endpoint pakai ID teknisi
        $response = $this->post(route('laporan.sales.collect.store', $teknisi->id), [
            'customers' => [['nama_customer' => 'Cust', 'alamat_customer' => 'Alamat', 'provider' => 'Biznet']]
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseCount('laporan_sales', 0);
    }

    public function test_sales_collect_berhasil_untuk_sales(): void
    {
        $siswa = $this->makeSiswa('s10','S10','G1-010');
        $this->loginAs($siswa);
        $this->buatAbsensiMasuk($siswa);
        $response = $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Laporan Sales minimal 10 karakter untuk test sales parent',
            'fotos' => [$this->fakeFoto()],
        ]);
        $salesParent = Laporan::where('user_id', $siswa->id)->first();
        $this->assertNotNull($salesParent);
        $response = $this->post(route('laporan.sales.collect.store', $salesParent->id), [
            'customers' => [['nama_customer' => 'Cust Sales', 'alamat_customer' => 'Alamat', 'provider' => 'Biznet']]
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('laporan_sales', ['nama_customer' => 'Cust Sales', 'laporan_id' => $salesParent->id]);
    }

    public function test_sales_collect_ditolak_milik_orang_lain(): void
    {
        $siswa1 = $this->makeSiswa('s11','S11','G1-011');
        $siswa2 = $this->makeSiswa('s12','S12','G1-012');
        $this->loginAs($siswa1);
        $this->buatAbsensiMasuk($siswa1);
        $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Laporan Sales milik s11 minimal 10 karakter',
            'fotos' => [$this->fakeFoto()],
        ]);
        $salesParent = Laporan::where('user_id', $siswa1->id)->first();
        $this->loginAs($siswa2);
        $response = $this->post(route('laporan.sales.collect.store', $salesParent->id), [
            'customers' => [['nama_customer' => 'Hacker', 'alamat_customer' => 'Alamat', 'provider' => 'Biznet']]
        ]);
        $response->assertStatus(403);
    }

    // 7. Sales parent tidak muncul di Teknisi list
    // Aturan 1 laporan/hari: Teknisi dan Sales tidak boleh dibuat pada tanggal
    // yang sama oleh siswa yang sama, sehingga skenario uji memakai 2 tanggal.
    public function test_sales_parent_tidak_muncul_di_teknisi(): void
    {
        $siswa = $this->makeSiswa('s13','S13','G1-013');
        $this->loginAs($siswa);
        $this->buatAbsensiMasuk($siswa);
        // Buat Teknisi (tanggal 2026-09-08)
        $this->post(route('laporan.store'), [
            'teks_laporan' => 'Teknisi laporan untuk filter test minimal 10 karakter',
            'fotos' => [$this->fakeFoto()],
        ]);
        $this->assertDatabaseCount('laporans', 1);
        // Aturan baru: Sales pada tanggal yang SAMA harus ditolak
        $ditolak = $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Sales laporan untuk filter test minimal 10 karakter',
            'fotos' => [$this->fakeFoto()],
        ]);
        $ditolak->assertSessionHas('sales_error');
        $this->assertDatabaseCount('laporans', 1);
        // Hari berikutnya: Sales boleh dibuat (tanggal berbeda)
        Carbon::setTestNow('2026-09-09 10:00:00');
        WaktuAbsen::flush();
        $this->buatAbsensiMasuk($siswa);
        $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Sales laporan untuk filter test minimal 10 karakter',
            'fotos' => [$this->fakeFoto()],
        ]);
        $this->assertDatabaseCount('laporans', 2);
        $salesParent = Laporan::where('user_id', $siswa->id)->orderByDesc('id')->first();
        $this->assertEquals(Laporan::JENIS_SALES, $salesParent->jenis_laporan);
        $this->post(route('laporan.sales.collect.store', $salesParent->id), [
            'customers' => [['nama_customer' => 'Cust Filter', 'alamat_customer' => 'Alamat', 'provider' => 'Biznet']]
        ]);
        // GET Teknisi index harus tidak mengandung teks Sales
        $response = $this->get(route('laporan.index'));
        $response->assertOk();
        // Sales parent teks should not be in Teknisi list (karena sudah ada sales)
        $response->assertDontSee('Sales laporan untuk filter test');
        $response->assertSee('Teknisi laporan untuk filter test');
    }
}
