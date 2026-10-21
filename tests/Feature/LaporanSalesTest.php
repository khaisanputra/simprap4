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

class LaporanSalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-10 10:00:00'); // within 08:00-23:59
        Storage::fake('public');
    }

    protected function createSiswa($username = 'siswa1', $nama = 'Siswa Satu'): User
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

    protected function createAdmin(): User
    {
        return User::create([
            'username' => 'admin',
            'email' => 'admin@tes.com',
            'nama' => 'Administrator',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'Aktif',
        ]);
    }

    protected function loginAs(User $user): void
    {
        $this->withSession([
            'user_id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
        ]);
        $this->actingAs($user);
    }

    protected function buatAbsensiMasuk(User $user, string $status = 'Masuk'): void
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

    protected function fakeFoto(): UploadedFile
    {
        // Create a minimal valid JPEG via GD if available, else use fake image
        // Use UploadedFile::fake()->image which creates a valid image
        return UploadedFile::fake()->image('test.jpg', 200, 200)->size(100);
    }

    protected function createLaporanSalesUtama(User $siswa): Laporan
    {
        // Helper to create tahap 1 laporan via DB (simulate store)
        $laporan = Laporan::create([
            'user_id' => $siswa->id,
            'tanggal_laporan' => '2026-09-10',
            'teks_laporan' => 'Laporan Sales test teks laporan minimal 10 karakter abcdef',
            'jenis_laporan' => Laporan::JENIS_SALES,
        ]);
        // Also create a dummy foto record to satisfy relation
        $laporan->fotos()->create([
            'foto' => 'laporan/foto/dummy.jpg',
            'foto_original_name' => 'dummy.jpg',
            'foto_mime_type' => 'image/jpeg',
            'sort_order' => 0,
        ]);
        Storage::disk('public')->put('laporan/foto/dummy.jpg', 'fake');
        return $laporan;
    }

    // ========== TAHAP 1 TESTS ==========

    public function test_siswa_can_create_sales(): void
    {
        // Tahap 1: Form Laporan Sales sekarang menyimpan ke laporans dan redirect ke collect
        $siswa = $this->createSiswa('hanung', 'Hanung');
        $this->loginAs($siswa);
        $this->buatAbsensiMasuk($siswa);

        $response = $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Ini teks laporan Sales minimal 10 karakter untuk testing',
            'fotos' => [$this->fakeFoto()],
        ]);

        // Should redirect to collect page
        $laporan = Laporan::where('user_id', $siswa->id)->first();
        $this->assertNotNull($laporan);
        $response->assertRedirect(route('laporan.sales.collect', $laporan->id));
        $this->assertDatabaseHas('laporans', [
            'teks_laporan' => 'Ini teks laporan Sales minimal 10 karakter untuk testing',
            'user_id' => $siswa->id,
        ]);
        // Tahap 1 belum membuat laporan_sales
        $this->assertDatabaseCount('laporan_sales', 0);
        // Tanggal otomatis hari ini
        $this->assertEquals('2026-09-10', Carbon::parse($laporan->tanggal_laporan)->format('Y-m-d'));
    }

    public function test_sales_tidak_masuk_ke_teknisi_tapi_ke_laporans(): void
    {
        // Sales tahap 1 masuk ke laporans, bukan laporan_sales
        $siswa = $this->createSiswa();
        $this->loginAs($siswa);
        $this->buatAbsensiMasuk($siswa);
        $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Teks laporan Sales valid minimal 10 karakter',
            'fotos' => [$this->fakeFoto()],
        ]);
        $this->assertDatabaseCount('laporan_sales', 0);
        $this->assertDatabaseCount('laporans', 1);
    }

    public function test_sales_form_memerlukan_foto(): void
    {
        $siswa = $this->createSiswa();
        $this->loginAs($siswa);
        $response = $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Teks valid minimal 10 karakter',
            // no fotos
        ]);
        $response->assertSessionHasErrors('fotos');
    }

    public function test_sales_form_foto_maksimal_6(): void
    {
        $siswa = $this->createSiswa();
        $this->loginAs($siswa);
        $fotos = [];
        for ($i=0;$i<7;$i++) $fotos[] = $this->fakeFoto();
        $response = $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Teks valid minimal 10 karakter',
            'fotos' => $fotos,
        ]);
        $response->assertSessionHasErrors('fotos');
    }

    public function test_sales_form_teks_minimal_10(): void
    {
        $siswa = $this->createSiswa();
        $this->loginAs($siswa);
        $response = $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'pendek',
            'fotos' => [$this->fakeFoto()],
        ]);
        $response->assertSessionHasErrors('teks_laporan');
    }

    // ========== TAHAP 2 COLLECT TESTS ==========

    public function test_collect_can_store_single_customer(): void
    {
        $siswa = $this->createSiswa();
        $laporan = $this->createLaporanSalesUtama($siswa);
        $this->loginAs($siswa);
        $this->buatAbsensiMasuk($siswa);

        $response = $this->post(route('laporan.sales.collect.store', $laporan->id), [
            'customers' => [
                [
                    'nama_customer' => 'Budi Customer',
                    'no_telp' => '08123456789',
                    'alamat_customer' => 'Jl. Test No 1',
                    'provider' => 'Biznet',
                    'kelebihan' => 'Cepat',
                    'kekurangan' => 'Mahal',
                ]
            ]
        ]);

        $response->assertRedirect(route('laporan.sales.data'));
        $this->assertDatabaseHas('laporan_sales', [
            'nama_customer' => 'Budi Customer',
            'provider' => 'Biznet',
            'user_id' => $siswa->id,
            'laporan_id' => $laporan->id,
        ]);
        $sales = LaporanSales::where('laporan_id', $laporan->id)->first();
        $this->assertEquals('2026-09-10', $sales->tanggal->format('Y-m-d'));
    }

    public function test_collect_can_store_10_customers(): void
    {
        $siswa = $this->createSiswa();
        $laporan = $this->createLaporanSalesUtama($siswa);
        $this->loginAs($siswa);
        $this->buatAbsensiMasuk($siswa);

        $customers = [];
        for ($i=1;$i<=10;$i++) {
            $customers[] = [
                'nama_customer' => "Customer $i",
                'alamat_customer' => "Alamat $i",
                'provider' => 'Biznet',
            ];
        }
        $response = $this->post(route('laporan.sales.collect.store', $laporan->id), [
            'customers' => $customers
        ]);
        $response->assertRedirect(route('laporan.sales.data'));
        $this->assertDatabaseCount('laporan_sales', 10);
        $this->assertEquals(10, LaporanSales::where('laporan_id', $laporan->id)->count());
    }

    public function test_collect_tidak_bisa_11(): void
    {
        $siswa = $this->createSiswa();
        $laporan = $this->createLaporanSalesUtama($siswa);
        $this->loginAs($siswa);
        $customers = [];
        for ($i=1;$i<=11;$i++) {
            $customers[] = [
                'nama_customer' => "Customer $i",
                'alamat_customer' => "Alamat $i",
                'provider' => 'Biznet',
            ];
        }
        $response = $this->post(route('laporan.sales.collect.store', $laporan->id), [
            'customers' => $customers
        ]);
        $response->assertSessionHasErrors('customers');
        $this->assertDatabaseCount('laporan_sales', 0);
    }

    public function test_collect_minimal_1(): void
    {
        $siswa = $this->createSiswa();
        $laporan = $this->createLaporanSalesUtama($siswa);
        $this->loginAs($siswa);
        $response = $this->post(route('laporan.sales.collect.store', $laporan->id), [
            'customers' => []
        ]);
        $response->assertSessionHasErrors('customers');
    }

    public function test_provider_dropdown_wajib_20_pilihan(): void
    {
        $this->assertCount(20, LaporanSales::PROVIDERS);
        $this->assertEquals('Lintas Data', LaporanSales::PROVIDERS[0]);
        $this->assertEquals('Azafanet', LaporanSales::PROVIDERS[19]);
        // Cek invalid provider ditolak via collect
        $siswa = $this->createSiswa();
        $laporan = $this->createLaporanSalesUtama($siswa);
        $this->loginAs($siswa);
        $response = $this->post(route('laporan.sales.collect.store', $laporan->id), [
            'customers' => [
                ['nama_customer' => 'Test', 'alamat_customer' => 'Alamat', 'provider' => 'Provider Tidak Valid']
            ]
        ]);
        $response->assertSessionHasErrors('customers.0.provider');
    }

    public function test_collector_otomatis_dari_login(): void
    {
        $siswa = $this->createSiswa('siswaA', 'Siswa A');
        $laporan = $this->createLaporanSalesUtama($siswa);
        $this->loginAs($siswa);
        $this->buatAbsensiMasuk($siswa);
        // Coba manipulasi user_id via request — should be ignored, use auth user
        $this->post(route('laporan.sales.collect.store', $laporan->id), [
            'customers' => [
                ['nama_customer' => 'Cust', 'alamat_customer' => 'Alamat', 'provider' => 'Biznet', 'user_id' => 9999]
            ]
        ]);
        $this->assertDatabaseHas('laporan_sales', ['user_id' => $siswa->id, 'laporan_id' => $laporan->id]);
        $this->assertDatabaseMissing('laporan_sales', ['user_id' => 9999]);
    }

    public function test_tanggal_otomatis_dari_laporan(): void
    {
        $siswa = $this->createSiswa();
        $laporan = $this->createLaporanSalesUtama($siswa);
        $this->loginAs($siswa);
        $this->buatAbsensiMasuk($siswa);
        $this->post(route('laporan.sales.collect.store', $laporan->id), [
            'customers' => [
                ['nama_customer' => 'Cust', 'alamat_customer' => 'Alamat', 'provider' => 'Biznet', 'tanggal' => '2026-09-09']
            ]
        ]);
        $sales = LaporanSales::where('laporan_id', $laporan->id)->first();
        $this->assertEquals('2026-09-10', $sales->tanggal->format('Y-m-d'));
    }

    public function test_jam_sebelum_0800_ditolak(): void
    {
        Carbon::setTestNow('2026-09-10 07:30:00');
        $siswa = $this->createSiswa();
        $this->loginAs($siswa);
        $response = $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Teks valid minimal 10 karakter untuk jam test',
            'fotos' => [$this->fakeFoto()],
        ]);
        $response->assertSessionHas('sales_error');
        $this->assertDatabaseCount('laporans', 0);
    }

    public function test_jam_0800_diterima(): void
    {
        Carbon::setTestNow('2026-09-10 08:00:00');
        $siswa = $this->createSiswa();
        $this->loginAs($siswa);
        $this->buatAbsensiMasuk($siswa);
        $response = $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Teks valid minimal 10 karakter untuk jam test',
            'fotos' => [$this->fakeFoto()],
        ]);
        $response->assertRedirect();
        $this->assertDatabaseCount('laporans', 1);
    }

    public function test_jam_2359_diterima(): void
    {
        Carbon::setTestNow('2026-09-10 23:59:00');
        $siswa = $this->createSiswa();
        $this->loginAs($siswa);
        $this->buatAbsensiMasuk($siswa);
        $response = $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Teks valid minimal 10 karakter untuk jam test',
            'fotos' => [$this->fakeFoto()],
        ]);
        $this->assertDatabaseCount('laporans', 1);
    }

    public function test_jam_collect_sebelum_0800_ditolak(): void
    {
        $siswa = $this->createSiswa();
        $laporan = $this->createLaporanSalesUtama($siswa);
        Carbon::setTestNow('2026-09-10 07:30:00');
        $this->loginAs($siswa);
        $response = $this->post(route('laporan.sales.collect.store', $laporan->id), [
            'customers' => [['nama_customer' => 'Cust', 'alamat_customer' => 'Alamat', 'provider' => 'Biznet']]
        ]);
        $response->assertSessionHas('sales_error');
        $this->assertDatabaseCount('laporan_sales', 0);
    }

    public function test_admin_tidak_bisa_create_sales(): void
    {
        $admin = $this->createAdmin();
        $this->loginAs($admin);
        $response = $this->post(route('laporan.sales.store'), [
            'teks_laporan' => 'Teks admin tidak boleh minimal 10 karakter ok',
            'fotos' => [$this->fakeFoto()],
        ]);
        $response->assertRedirect(route('laporan.sales.index'));
        $response->assertSessionHas('sales_error');
        $this->assertDatabaseCount('laporans', 0);
    }

    public function test_admin_tidak_bisa_collect(): void
    {
        $siswa = $this->createSiswa();
        $laporan = $this->createLaporanSalesUtama($siswa);
        $admin = $this->createAdmin();
        $this->loginAs($admin);
        $response = $this->post(route('laporan.sales.collect.store', $laporan->id), [
            'customers' => [['nama_customer' => 'Cust', 'alamat_customer' => 'Alamat', 'provider' => 'Biznet']]
        ]);
        $response->assertRedirect(route('laporan.sales.data'));
        $response->assertSessionHas('sales_error');
        $this->assertDatabaseCount('laporan_sales', 0);
    }

    public function test_filter_tanggal(): void
    {
        $siswa = $this->createSiswa();
        LaporanSales::create([
            'user_id' => $siswa->id,
            'tanggal' => '2026-09-10',
            'nama_customer' => 'Cust 10',
            'alamat_customer' => 'Alamat',
            'provider' => 'Biznet',
        ]);
        LaporanSales::create([
            'user_id' => $siswa->id,
            'tanggal' => '2026-09-11',
            'nama_customer' => 'Cust 11',
            'alamat_customer' => 'Alamat',
            'provider' => 'Indihome',
        ]);
        $this->loginAs($siswa);
        $response = $this->get(route('laporan.sales.data', ['tanggal' => '2026-09-10']));
        $response->assertSee('Cust 10');
        $response->assertDontSee('Cust 11');
    }

    public function test_filter_provider(): void
    {
        $siswa = $this->createSiswa();
        LaporanSales::create([
            'user_id' => $siswa->id,
            'tanggal' => '2026-09-10',
            'nama_customer' => 'Cust Biznet',
            'alamat_customer' => 'Alamat',
            'provider' => 'Biznet',
        ]);
        LaporanSales::create([
            'user_id' => $siswa->id,
            'tanggal' => '2026-09-10',
            'nama_customer' => 'Cust Indihome',
            'alamat_customer' => 'Alamat',
            'provider' => 'Indihome',
        ]);
        $this->loginAs($siswa);
        $response = $this->get(route('laporan.sales.data', ['provider' => 'Biznet']));
        $response->assertSee('Cust Biznet');
        $response->assertDontSee('Cust Indihome');
    }

    public function test_filter_collector_admin(): void
    {
        $siswa1 = $this->createSiswa('siswa1', 'Siswa Satu');
        $siswa2 = $this->createSiswa('siswa2', 'Siswa Dua');
        LaporanSales::create([
            'user_id' => $siswa1->id,
            'tanggal' => '2026-09-10',
            'nama_customer' => 'Cust Satu',
            'alamat_customer' => 'Alamat',
            'provider' => 'Biznet',
        ]);
        LaporanSales::create([
            'user_id' => $siswa2->id,
            'tanggal' => '2026-09-10',
            'nama_customer' => 'Cust Dua',
            'alamat_customer' => 'Alamat',
            'provider' => 'Biznet',
        ]);
        $admin = $this->createAdmin();
        $this->loginAs($admin);
        $response = $this->get(route('laporan.sales.data', ['collector_id' => $siswa1->id]));
        $response->assertSee('Cust Satu');
        $response->assertDontSee('Cust Dua');
    }

    public function test_siswa_hanya_lihat_miliknya(): void
    {
        $siswa1 = $this->createSiswa('siswa1', 'Siswa Satu');
        $siswa2 = $this->createSiswa('siswa2', 'Siswa Dua');
        LaporanSales::create([
            'user_id' => $siswa1->id,
            'tanggal' => '2026-09-10',
            'nama_customer' => 'Cust Satu',
            'alamat_customer' => 'Alamat',
            'provider' => 'Biznet',
        ]);
        LaporanSales::create([
            'user_id' => $siswa2->id,
            'tanggal' => '2026-09-10',
            'nama_customer' => 'Cust Dua',
            'alamat_customer' => 'Alamat',
            'provider' => 'Biznet',
        ]);
        $this->loginAs($siswa1);
        $response = $this->get(route('laporan.sales.data'));
        $response->assertSee('Cust Satu');
        $response->assertDontSee('Cust Dua');
    }

    public function test_export_mengikuti_filter(): void
    {
        $siswa = $this->createSiswa();
        LaporanSales::create([
            'user_id' => $siswa->id,
            'tanggal' => '2026-09-10',
            'nama_customer' => 'Cust Biznet',
            'alamat_customer' => 'Alamat',
            'provider' => 'Biznet',
        ]);
        LaporanSales::create([
            'user_id' => $siswa->id,
            'tanggal' => '2026-09-10',
            'nama_customer' => 'Cust Indihome',
            'alamat_customer' => 'Alamat',
            'provider' => 'Indihome',
        ]);
        $this->loginAs($siswa);
        $response = $this->get(route('laporan.sales.export', ['provider' => 'Biznet']));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_validasi_wajib(): void
    {
        $siswa = $this->createSiswa();
        $laporan = $this->createLaporanSalesUtama($siswa);
        $this->loginAs($siswa);
        $response = $this->post(route('laporan.sales.collect.store', $laporan->id), [
            'customers' => [
                ['nama_customer' => '', 'alamat_customer' => '', 'provider' => '']
            ]
        ]);
        $response->assertSessionHasErrors(['customers.0.nama_customer', 'customers.0.alamat_customer', 'customers.0.provider']);
    }

    public function test_validasi_tahap1_wajib(): void
    {
        $siswa = $this->createSiswa();
        $this->loginAs($siswa);
        $response = $this->post(route('laporan.sales.store'), [
            'teks_laporan' => '',
        ]);
        $response->assertSessionHasErrors(['teks_laporan', 'fotos']);
    }

    public function test_teknisi_tetap_berfungsi(): void
    {
        $siswa = $this->createSiswa();
        $this->loginAs($siswa);
        $response = $this->get(route('laporan.index'));
        $response->assertOk();
        $response->assertSee('Teknisi');
        $response->assertSee('Sales');
    }

    public function test_sales_form_mirip_teknisi(): void
    {
        $siswa = $this->createSiswa();
        $this->loginAs($siswa);
        $response = $this->get(route('laporan.sales.index'));
        $response->assertOk();
        $response->assertSee('Teks Laporan', false);
        $response->assertSee('Foto Dokumentasi', false);
        $response->assertSee('fotos[]', false);
        $response->assertSee('Simpan Laporan', false);
    }

    public function test_collect_route_terpisah(): void
    {
        $siswa = $this->createSiswa();
        $laporan = $this->createLaporanSalesUtama($siswa);
        $this->loginAs($siswa);
        $response = $this->get(route('laporan.sales.collect', $laporan->id));
        $response->assertOk();
        $response->assertSee('Collect Data Sales', false);
        $response->assertSee('Tambah Data Customer', false);
        $response->assertSee('Data Customer #', false);
    }

    public function test_relasi_laporan_id(): void
    {
        $siswa = $this->createSiswa();
        $laporan = $this->createLaporanSalesUtama($siswa);
        $this->loginAs($siswa);
        $this->buatAbsensiMasuk($siswa);
        $this->post(route('laporan.sales.collect.store', $laporan->id), [
            'customers' => [
                ['nama_customer' => 'A', 'alamat_customer' => 'Alamat A', 'provider' => 'Biznet'],
                ['nama_customer' => 'B', 'alamat_customer' => 'Alamat B', 'provider' => 'Indihome'],
                ['nama_customer' => 'C', 'alamat_customer' => 'Alamat C', 'provider' => 'Lintas Data'],
            ]
        ]);
        $this->assertDatabaseCount('laporan_sales', 3);
        foreach (LaporanSales::all() as $row) {
            $this->assertEquals($laporan->id, $row->laporan_id);
        }
        $laporan->refresh();
        $this->assertCount(3, $laporan->sales);
        $this->assertCount(3, $laporan->salesData);
        $sales = LaporanSales::first();
        $this->assertEquals($laporan->id, $sales->laporan->id);
    }

    public function test_data_lama_tetap_aman(): void
    {
        $siswa = $this->createSiswa();
        // Buat data lama tanpa laporan_id (simulasi data sebelum migrasi)
        $old = LaporanSales::create([
            'user_id' => $siswa->id,
            'tanggal' => '2026-09-05',
            'nama_customer' => 'Old Customer',
            'alamat_customer' => 'Alamat lama',
            'provider' => 'Biznet',
            'laporan_id' => null,
        ]);
        $this->assertDatabaseHas('laporan_sales', ['nama_customer' => 'Old Customer', 'laporan_id' => null]);
        // Pastikan tetap bisa ditampilkan di Data Sales
        $this->loginAs($siswa);
        $response = $this->get(route('laporan.sales.data'));
        $response->assertSee('Old Customer');
        // Export tetap ok
        $response = $this->get(route('laporan.sales.export'));
        $response->assertOk();
    }

    public function test_transaction_rollback_jika_gagal(): void
    {
        $siswa = $this->createSiswa();
        $laporan = $this->createLaporanSalesUtama($siswa);
        $this->loginAs($siswa);
        // Kirim 2 data, salah satu invalid provider -> semua harus rollback (tidak ada yang tersimpan)
        $response = $this->post(route('laporan.sales.collect.store', $laporan->id), [
            'customers' => [
                ['nama_customer' => 'Valid', 'alamat_customer' => 'Alamat', 'provider' => 'Biznet'],
                ['nama_customer' => 'Invalid', 'alamat_customer' => 'Alamat', 'provider' => 'InvalidProvider'],
            ]
        ]);
        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('laporan_sales', 0);
    }

    public function test_tidak_bisa_collect_milik_orang_lain(): void
    {
        $siswa1 = $this->createSiswa('siswa1');
        $siswa2 = $this->createSiswa('siswa2');
        $laporan = $this->createLaporanSalesUtama($siswa1);
        $this->loginAs($siswa2);
        $response = $this->get(route('laporan.sales.collect', $laporan->id));
        $response->assertStatus(403);
        $response = $this->post(route('laporan.sales.collect.store', $laporan->id), [
            'customers' => [['nama_customer' => 'Hacker', 'alamat_customer' => 'Alamat', 'provider' => 'Biznet']]
        ]);
        $response->assertStatus(403);
    }
}
