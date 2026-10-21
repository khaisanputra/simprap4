<?php

namespace Tests\Feature;

use App\Models\Laporan;
use App\Models\User;
use App\Support\WaktuAbsen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Aturan: 1 SISWA + 1 TANGGAL = MAKSIMAL 1 LAPORAN UTAMA.
 * Berlaku lintas jenis (Teknisi maupun Sales).
 */
class LaporanSatuPerHariTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-30 10:00:00'); // dalam jam laporan 08:00-23:59
        Storage::fake('public');
    }

    protected function buatSiswa(string $username = 'siswa1', string $nama = 'Siswa Satu'): User
    {
        return User::create([
            'username' => $username,
            'email' => $username . '@tes.com',
            'nama' => $nama,
            'password' => Hash::make('password123'),
            'role' => 'siswa',
            'status' => 'Aktif',
        ]);
    }

    protected function loginSebagai(User $user): void
    {
        $this->actingAs($user);
    }

    protected function buatAbsensiMasuk(User $user): void
    {
        \App\Models\Attendance::create([
            'user_id' => $user->id,
            'nama' => $user->nama,
            'status' => 'Masuk',
            'status_kehadiran' => 'Masuk',
            'tgl_masuk' => now()->toDateString(),
            'jam_masuk' => '08:00:00',
            'tgl_pulang' => null,
            'jam_pulang' => null,
        ]);
    }

    protected function fotoPalsu(): UploadedFile
    {
        return UploadedFile::fake()->image('test.jpg', 200, 200)->size(100);
    }

    // A. Siswa membuat laporan Teknisi hari ini → berhasil.
    public function test_A_teknisi_hari_ini_berhasil(): void
    {
        $siswa = $this->buatSiswa();
        $this->loginSebagai($siswa);
        $this->buatAbsensiMasuk($siswa);

        $response = $this->post(route('laporan.store'), [
            'teks_laporan' => 'Kegiatan teknisi hari ini minimal sepuluh karakter untuk uji A',
            'fotos' => [$this->fotoPalsu()],
        ]);

        $response->assertRedirect(route('laporan.index'));
        $this->assertDatabaseCount('laporans', 1);
        $laporan = Laporan::where('user_id', $siswa->id)->first();
        $this->assertNotNull($laporan);
        $this->assertEquals('2026-09-30', $laporan->tanggal_laporan->format('Y-m-d'));
        $this->assertTrue($laporan->jenis_laporan === Laporan::JENIS_TEKNISI || $laporan->jenis_laporan === null);
    }

    // B. Siswa mencoba membuat laporan Sales pada tanggal yang sama → ditolak.
    public function test_B_sales_tanggal_sama_setelah_teknisi_ditolak(): void
    {
        $siswa = $this->buatSiswa();
        $this->loginSebagai($siswa);
        $this->buatAbsensiMasuk($siswa);

        $this->post(route('laporan.store'), [
            'teks_laporan' => 'Kegiatan teknisi hari ini minimal sepuluh karakter untuk uji B',
            'fotos' => [$this->fotoPalsu()],
        ]);
        $pertama = Laporan::where('user_id', $siswa->id)->first();
        $this->assertNotNull($pertama);

        // Upaya langsung via request (bypass UI) wajib ditolak backend.
        $response = $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Upaya sales tanggal sama yang wajib ditolak backend',
            'fotos' => [$this->fotoPalsu()],
        ]);

        $response->assertRedirect(route('laporan.show', $pertama->id));
        $response->assertSessionHas('sales_error', 'Laporan hari ini sudah dibuat. Silakan edit laporan yang tersedia.');
        $this->assertDatabaseCount('laporans', 1);
    }

    // C. Siswa membuat laporan Sales hari ini → berhasil.
    public function test_C_sales_hari_ini_berhasil(): void
    {
        $siswa = $this->buatSiswa();
        $this->loginSebagai($siswa);
        $this->buatAbsensiMasuk($siswa);

        $response = $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Kegiatan sales hari ini minimal sepuluh karakter untuk uji C',
            'fotos' => [$this->fotoPalsu()],
        ]);

        $laporan = Laporan::where('user_id', $siswa->id)->first();
        $this->assertNotNull($laporan);
        $response->assertRedirect(route('laporan.sales.collect', $laporan->id));
        $this->assertDatabaseCount('laporans', 1);
        $this->assertEquals(Laporan::JENIS_SALES, $laporan->jenis_laporan);
        $this->assertEquals('2026-09-30', $laporan->tanggal_laporan->format('Y-m-d'));
    }

    // D. Siswa mencoba membuat laporan Teknisi pada tanggal yang sama → ditolak.
    public function test_D_teknisi_tanggal_sama_setelah_sales_ditolak(): void
    {
        $siswa = $this->buatSiswa();
        $this->loginSebagai($siswa);
        $this->buatAbsensiMasuk($siswa);

        $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Kegiatan sales hari ini minimal sepuluh karakter untuk uji D',
            'fotos' => [$this->fotoPalsu()],
        ]);
        $pertama = Laporan::where('user_id', $siswa->id)->first();
        $this->assertNotNull($pertama);

        $response = $this->post(route('laporan.store'), [
            'teks_laporan' => 'Upaya teknisi tanggal sama yang wajib ditolak backend',
            'fotos' => [$this->fotoPalsu()],
        ]);

        $response->assertRedirect(route('laporan.show', $pertama->id));
        $response->assertSessionHas('laporan_error', 'Laporan hari ini sudah dibuat. Silakan edit laporan yang tersedia.');
        $this->assertDatabaseCount('laporans', 1);
    }

    // E. Siswa membuat laporan pada tanggal berbeda → berhasil.
    public function test_E_tanggal_berbeda_berhasil(): void
    {
        $siswa = $this->buatSiswa();
        $this->loginSebagai($siswa);
        $this->buatAbsensiMasuk($siswa);

        $this->post(route('laporan.store'), [
            'teks_laporan' => 'Laporan hari pertama yang valid isinya untuk uji E',
            'fotos' => [$this->fotoPalsu()],
        ]);
        $this->assertDatabaseCount('laporans', 1);

        // Hari berikutnya: boleh membuat laporan baru (jenis boleh berbeda).
        Carbon::setTestNow('2026-10-01 10:00:00');
        WaktuAbsen::flush();
        $this->buatAbsensiMasuk($siswa);

        $response = $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Laporan hari kedua yang valid isinya untuk uji E sales',
            'fotos' => [$this->fotoPalsu()],
        ]);

        $this->assertDatabaseCount('laporans', 2);
        $kedua = Laporan::where('user_id', $siswa->id)->orderByDesc('id')->first();
        $response->assertRedirect(route('laporan.sales.collect', $kedua->id));
        $this->assertEquals('2026-10-01', $kedua->tanggal_laporan->format('Y-m-d'));
    }

    // Siswa berbeda pada tanggal sama tetap boleh (batas per siswa, bukan global).
    public function test_siswa_berbeda_tanggal_sama_tetap_boleh(): void
    {
        $siswa1 = $this->buatSiswa('siswa1', 'Siswa Satu');
        $siswa2 = $this->buatSiswa('siswa2', 'Siswa Dua');

        $this->loginSebagai($siswa1);
        $this->buatAbsensiMasuk($siswa1);
        $this->post(route('laporan.store'), [
            'teks_laporan' => 'Laporan siswa satu yang valid isinya tanggal sama',
            'fotos' => [$this->fotoPalsu()],
        ]);

        $this->loginSebagai($siswa2);
        $this->buatAbsensiMasuk($siswa2);
        $response = $this->post(route('laporan.store'), [
            'teks_laporan' => 'Laporan siswa dua yang valid isinya tanggal sama',
            'fotos' => [$this->fotoPalsu()],
        ]);

        $response->assertRedirect(route('laporan.index'));
        $this->assertDatabaseCount('laporans', 2);
    }
}
