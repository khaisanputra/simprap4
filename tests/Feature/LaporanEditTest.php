<?php

namespace Tests\Feature;

use App\Models\Laporan;
use App\Models\LaporanSales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Edit laporan: ID tetap, pemilik tetap, tanggal tetap,
 * relasi Sales tetap, data collect tidak diduplikasi.
 * Foto: tambah ke foto lama, maksimal 6 (behavior tidak diubah).
 */
class LaporanEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-30 10:00:00');
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

    protected function fotoPalsu(string $nama = 'test.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($nama, 200, 200)->size(100);
    }

    protected function buatTeknisi(User $siswa, string $teks = 'Teks awal laporan teknisi yang valid isinya'): Laporan
    {
        $this->loginSebagai($siswa);
        $this->buatAbsensiMasuk($siswa);
        $this->post(route('laporan.store'), [
            'teks_laporan' => $teks,
            'fotos' => [$this->fotoPalsu()],
        ]);
        return Laporan::where('user_id', $siswa->id)->firstOrFail();
    }

    protected function buatSales(User $siswa, string $teks = 'Teks awal laporan sales yang valid isinya'): Laporan
    {
        $this->loginSebagai($siswa);
        $this->buatAbsensiMasuk($siswa);
        $this->post(route('laporan.sales.store'), [
            'teks_laporan' => $teks,
            'fotos' => [$this->fotoPalsu()],
        ]);
        return Laporan::where('user_id', $siswa->id)->firstOrFail();
    }

    // TEST TEKNISI: ID, user_id, tanggal tetap; teks berubah; tidak ada record baru.
    public function test_edit_teknisi_berhasil_id_pemilik_tanggal_tetap(): void
    {
        $siswa = $this->buatSiswa();
        $laporan = $this->buatTeknisi($siswa);
        $idAwal = $laporan->id;
        $tanggalAwal = $laporan->tanggal_laporan->format('Y-m-d');

        $halaman = $this->get(route('laporan.edit', $idAwal));
        $halaman->assertOk();
        $halaman->assertSee('Teks awal laporan teknisi', false);

        $response = $this->put(route('laporan.update', $idAwal), [
            'teks_laporan' => 'Teks edit laporan teknisi yang sudah diperbaiki isinya',
        ]);

        $response->assertRedirect(route('laporan.show', $idAwal));
        $response->assertSessionHas('laporan_success');
        $this->assertDatabaseCount('laporans', 1);

        $laporan->refresh();
        $this->assertEquals($idAwal, $laporan->id);
        $this->assertEquals($siswa->id, $laporan->user_id);
        $this->assertEquals($tanggalAwal, $laporan->tanggal_laporan->format('Y-m-d'));
        $this->assertEquals('Teks edit laporan teknisi yang sudah diperbaiki isinya', $laporan->teks_laporan);
    }

    // Edit Teknisi dengan tambahan foto: foto lama tetap + foto baru, total ≤ 6.
    public function test_edit_teknisi_tambah_foto_tidak_hapus_lama(): void
    {
        $siswa = $this->buatSiswa();
        $laporan = $this->buatTeknisi($siswa);
        $jumlahFotoAwal = $laporan->fotos()->count();
        $this->assertEquals(1, $jumlahFotoAwal);

        $this->put(route('laporan.update', $laporan->id), [
            'teks_laporan' => 'Teks edit dengan tambahan foto baru yang valid',
            'fotos' => [$this->fotoPalsu('baru.jpg')],
        ]);

        $this->assertDatabaseCount('laporans', 1);
        $laporan->refresh();
        $this->assertEquals(2, $laporan->fotos()->count());
        $this->assertEquals('Teks edit dengan tambahan foto baru yang valid', $laporan->teks_laporan);
    }

    // TEST SALES: ID, user_id, tanggal tetap; collect tidak diduplikasi; laporan_id tetap.
    public function test_edit_sales_tidak_duplikasi_collect(): void
    {
        $siswa = $this->buatSiswa();
        $parent = $this->buatSales($siswa);
        $idAwal = $parent->id;
        $tanggalAwal = $parent->tanggal_laporan->format('Y-m-d');

        $this->post(route('laporan.sales.collect.store', $idAwal), [
            'customers' => [
                ['nama_customer' => 'Cust Satu', 'alamat_customer' => 'Jl Satu', 'provider' => 'Biznet'],
                ['nama_customer' => 'Cust Dua', 'alamat_customer' => 'Jl Dua', 'provider' => 'Indihome'],
            ],
        ]);
        $this->assertEquals(2, LaporanSales::where('laporan_id', $idAwal)->count());

        $halaman = $this->get(route('laporan.sales.edit', $idAwal));
        $halaman->assertOk();

        $response = $this->put(route('laporan.sales.update', $idAwal), [
            'teks_laporan' => 'Teks edit laporan sales tanpa mengganggu collect',
        ]);

        $response->assertRedirect(route('laporan.show', $idAwal));
        $this->assertDatabaseCount('laporans', 1);

        $parent->refresh();
        $this->assertEquals($idAwal, $parent->id);
        $this->assertEquals($siswa->id, $parent->user_id);
        $this->assertEquals($tanggalAwal, $parent->tanggal_laporan->format('Y-m-d'));
        $this->assertEquals('Teks edit laporan sales tanpa mengganggu collect', $parent->teks_laporan);
        // Collect tidak diduplikasi dan tetap mengarah ke laporan yang sama.
        $this->assertEquals(2, LaporanSales::where('laporan_id', $idAwal)->count());
        foreach (LaporanSales::all() as $row) {
            $this->assertEquals($idAwal, $row->laporan_id);
        }
        $this->assertDatabaseHas('laporan_sales', ['nama_customer' => 'Cust Satu', 'laporan_id' => $idAwal]);
        $this->assertDatabaseHas('laporan_sales', ['nama_customer' => 'Cust Dua', 'laporan_id' => $idAwal]);
    }

    // AUTHORIZATION: Siswa B tidak boleh edit laporan Siswa A (GET 403, PUT 403, data utuh).
    public function test_siswa_lain_ditolak_edit_teknisi(): void
    {
        $siswaA = $this->buatSiswa('siswaA', 'Siswa A');
        $siswaB = $this->buatSiswa('siswaB', 'Siswa B');
        $laporan = $this->buatTeknisi($siswaA, 'Laporan rahasia milik siswa A yang valid');
        $teksAsli = $laporan->teks_laporan;

        $this->loginSebagai($siswaB);
        $this->get(route('laporan.edit', $laporan->id))->assertStatus(403);
        $this->put(route('laporan.update', $laporan->id), [
            'teks_laporan' => 'Upaya hacker mengubah laporan orang lain ini',
        ])->assertStatus(403);

        $laporan->refresh();
        $this->assertEquals($teksAsli, $laporan->teks_laporan);
        $this->assertEquals($siswaA->id, $laporan->user_id);
    }

    public function test_siswa_lain_ditolak_edit_sales(): void
    {
        $siswaA = $this->buatSiswa('siswaA', 'Siswa A');
        $siswaB = $this->buatSiswa('siswaB', 'Siswa B');
        $parent = $this->buatSales($siswaA, 'Laporan sales rahasia milik siswa A valid');
        $teksAsli = $parent->teks_laporan;

        $this->loginSebagai($siswaB);
        $this->get(route('laporan.sales.edit', $parent->id))->assertStatus(403);
        $this->put(route('laporan.sales.update', $parent->id), [
            'teks_laporan' => 'Upaya hacker mengubah sales orang lain ini',
        ])->assertStatus(403);

        $parent->refresh();
        $this->assertEquals($teksAsli, $parent->teks_laporan);
    }

    // MANIPULASI REQUEST: user_id, tanggal_laporan, jenis_laporan diabaikan.
    public function test_manipulasi_request_edit_teknisi_diabaikan(): void
    {
        $siswa = $this->buatSiswa();
        $korban = $this->buatSiswa('korban', 'Korban');
        $laporan = $this->buatTeknisi($siswa);
        $tanggalAwal = $laporan->tanggal_laporan->format('Y-m-d');

        $this->put(route('laporan.update', $laporan->id), [
            'teks_laporan' => 'Teks edit valid setelah upaya manipulasi request',
            'user_id' => $korban->id,
            'tanggal_laporan' => '2020-01-01',
            'jenis_laporan' => Laporan::JENIS_SALES,
        ]);

        $laporan->refresh();
        $this->assertEquals($siswa->id, $laporan->user_id);
        $this->assertEquals($tanggalAwal, $laporan->tanggal_laporan->format('Y-m-d'));
        $this->assertTrue($laporan->jenis_laporan === Laporan::JENIS_TEKNISI || $laporan->jenis_laporan === null);
        $this->assertEquals('Teks edit valid setelah upaya manipulasi request', $laporan->teks_laporan);
    }

    public function test_manipulasi_request_edit_sales_diabaikan(): void
    {
        $siswa = $this->buatSiswa();
        $korban = $this->buatSiswa('korban', 'Korban');
        $parent = $this->buatSales($siswa);
        $tanggalAwal = $parent->tanggal_laporan->format('Y-m-d');

        $this->put(route('laporan.sales.update', $parent->id), [
            'teks_laporan' => 'Teks edit sales valid setelah upaya manipulasi',
            'user_id' => $korban->id,
            'tanggal_laporan' => '2020-01-01',
            'jenis_laporan' => Laporan::JENIS_TEKNISI,
        ]);

        $parent->refresh();
        $this->assertEquals($siswa->id, $parent->user_id);
        $this->assertEquals($tanggalAwal, $parent->tanggal_laporan->format('Y-m-d'));
        $this->assertEquals(Laporan::JENIS_SALES, $parent->jenis_laporan);
    }

    // Edit lintas jenis ditolak (teknisi via route sales dan sebaliknya).
    public function test_edit_lintas_jenis_ditolak(): void
    {
        $siswa = $this->buatSiswa();
        $teknisi = $this->buatTeknisi($siswa);

        $this->put(route('laporan.sales.update', $teknisi->id), [
            'teks_laporan' => 'Upaya edit teknisi via route sales harus ditolak',
        ])->assertStatus(403);
        $this->get(route('laporan.sales.edit', $teknisi->id))->assertStatus(403);
    }
}
