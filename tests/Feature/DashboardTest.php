<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    protected function loginAsAdmin()
    {
        $admin = User::where('username', 'admin')->first();

        return $this->actingAs($admin, 'web')
            ->withSession(['user_id' => $admin->id, 'username' => $admin->username, 'role' => $admin->role]);
    }

    public function test_dashboard_page_loads(): void
    {
        $response = $this->loginAsAdmin()
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

public function test_dashboard_search_works(): void
    {
        $response = $this->loginAsAdmin()
            ->get(route('dashboard', ['cari' => 'siswa']));

        $response->assertStatus(200);
        $response->assertViewHas('activities');
    }

    public function test_export_pdf_removed_and_print_remains(): void
    {
        // PDF download harus sudah dihapus — route tidak boleh ada, Print tetap ada
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('dashboard.export.pdf'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('data-absen-masuk.export.pdf'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('laporan.pdf'));

        // Print tetap harus ada dan mengarah ke Save as PDF via browser
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('dashboard.print'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('data-absen-masuk.print'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('laporan.print'));

        $response = $this->loginAsAdmin()->get(route('dashboard.print'));
        $response->assertStatus(200);
    }

    public function test_export_excel_works(): void
    {
        $response = $this->loginAsAdmin()
            ->get(route('dashboard.export.excel'));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $response->headers->get('content-type'));
    }

    public function test_print_works(): void
    {
        $response = $this->loginAsAdmin()
            ->get(route('dashboard.print'));

        $response->assertStatus(200);
        $response->assertSee('Cetak');
    }
}
