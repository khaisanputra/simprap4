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
 * Batas maksimal 10 data customer per laporan Sales,
 * termasuk validasi akumulasi lintas submit berurutan
 * (existing + submit) dan proteksi transaksi/locking.
 */
class LaporanCollectBatasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-30 10:00:00');
        Storage::fake('public');
    }

    protected function buatSiswa(string $username = 'siswa1'): User
    {
        return User::create([
            'username' => $username,
            'email' => $username . '@tes.com',
            'nama' => 'Siswa ' . $username,
            'password' => Hash::make('password123'),
            'role' => 'siswa',
            'status' => 'Aktif',
        ]);
    }

    protected function buatParentSales(User $siswa): Laporan
    {
        $this->actingAs($siswa);
        \App\Models\Attendance::create([
            'user_id' => $siswa->id,
            'nama' => $siswa->nama,
            'status' => 'Masuk',
            'status_kehadiran' => 'Masuk',
            'tgl_masuk' => now()->toDateString(),
            'jam_masuk' => '08:00:00',
        ]);
        $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Laporan sales parent untuk uji batas collect data',
            'fotos' => [UploadedFile::fake()->image('a.jpg', 200, 200)->size(100)],
        ]);
        return Laporan::where('user_id', $siswa->id)->firstOrFail();
    }

    protected function buatCustomers(int $jumlah, string $prefix = 'Cust'): array
    {
        $out = [];
        for ($i = 1; $i <= $jumlah; $i++) {
            $out[] = [
                'nama_customer' => $prefix . ' ' . $i,
                'alamat_customer' => 'Alamat ' . $i,
                'provider' => 'Biznet',
            ];
        }
        return $out;
    }

    // existing 0 + 10 → berhasil.
    public function test_existing_0_tambah_10_berhasil(): void
    {
        $siswa = $this->buatSiswa();
        $parent = $this->buatParentSales($siswa);

        $response = $this->post(route('laporan.sales.collect.store', $parent->id), [
            'customers' => $this->buatCustomers(10),
        ]);

        $response->assertRedirect(route('laporan.sales.data'));
        $this->assertEquals(10, LaporanSales::where('laporan_id', $parent->id)->count());
    }

    // existing 0 + 11 → ditolak (validasi max:10).
    public function test_existing_0_tambah_11_ditolak(): void
    {
        $siswa = $this->buatSiswa();
        $parent = $this->buatParentSales($siswa);

        $response = $this->post(route('laporan.sales.collect.store', $parent->id), [
            'customers' => $this->buatCustomers(11),
        ]);

        $response->assertSessionHasErrors('customers');
        $this->assertEquals(0, LaporanSales::where('laporan_id', $parent->id)->count());
    }

    // existing 8 + 2 → berhasil (total pas 10).
    public function test_existing_8_tambah_2_berhasil(): void
    {
        $siswa = $this->buatSiswa();
        $parent = $this->buatParentSales($siswa);
        $this->post(route('laporan.sales.collect.store', $parent->id), [
            'customers' => $this->buatCustomers(8, 'Awal'),
        ]);
        $this->assertEquals(8, LaporanSales::where('laporan_id', $parent->id)->count());

        $response = $this->post(route('laporan.sales.collect.store', $parent->id), [
            'customers' => $this->buatCustomers(2, 'Tambah'),
        ]);

        $response->assertRedirect(route('laporan.sales.data'));
        $this->assertEquals(10, LaporanSales::where('laporan_id', $parent->id)->count());
    }

    // existing 8 + 3 → ditolak (total 11).
    public function test_existing_8_tambah_3_ditolak(): void
    {
        $siswa = $this->buatSiswa();
        $parent = $this->buatParentSales($siswa);
        $this->post(route('laporan.sales.collect.store', $parent->id), [
            'customers' => $this->buatCustomers(8, 'Awal'),
        ]);

        $response = $this->post(route('laporan.sales.collect.store', $parent->id), [
            'customers' => $this->buatCustomers(3, 'Lebih'),
        ]);

        $response->assertSessionHas('sales_error');
        $this->assertEquals(8, LaporanSales::where('laporan_id', $parent->id)->count());
    }

    // existing 10 + 1 → ditolak.
    public function test_existing_10_tambah_1_ditolak(): void
    {
        $siswa = $this->buatSiswa();
        $parent = $this->buatParentSales($siswa);
        $this->post(route('laporan.sales.collect.store', $parent->id), [
            'customers' => $this->buatCustomers(10, 'Penuh'),
        ]);
        $this->assertEquals(10, LaporanSales::where('laporan_id', $parent->id)->count());

        $response = $this->post(route('laporan.sales.collect.store', $parent->id), [
            'customers' => $this->buatCustomers(1, 'Satu'),
        ]);

        $response->assertSessionHas('sales_error');
        $this->assertEquals(10, LaporanSales::where('laporan_id', $parent->id)->count());
    }

    // Submit berurutan cepat tetap konsisten: 8 → +2 ok → +1 ditolak.
    public function test_submit_berurutan_tetap_konsisten(): void
    {
        $siswa = $this->buatSiswa();
        $parent = $this->buatParentSales($siswa);

        $this->post(route('laporan.sales.collect.store', $parent->id), [
            'customers' => $this->buatCustomers(8, 'Gel1'),
        ]);
        $this->post(route('laporan.sales.collect.store', $parent->id), [
            'customers' => $this->buatCustomers(2, 'Gel2'),
        ]);
        $response = $this->post(route('laporan.sales.collect.store', $parent->id), [
            'customers' => $this->buatCustomers(1, 'Gel3'),
        ]);

        $response->assertSessionHas('sales_error');
        $this->assertEquals(10, LaporanSales::where('laporan_id', $parent->id)->count());
    }
}
