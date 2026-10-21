<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup akun admin & siswa untuk pengujian akses.
     */
    protected function setUp(): void
    {
        parent::setUp();

        User::create([
            'username' => 'admin',
            'email'    => 'admin@tes.com',
            'nama'     => 'Administrator',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
            'status'   => 'Aktif',
        ]);

        User::create([
            'username' => 'siswa',
            'email'    => 'siswa@tes.com',
            'nama'     => 'Siswa Test',
            'password' => Hash::make('password123'),
            'role'     => 'siswa',
            'status'   => 'Aktif',
        ]);
    }

    /**
     * Login manual (set session + Auth) sesuai role.
     */
    protected function loginAs(string $role): User
    {
        $user = User::where('role', $role)->first();

        $this->withSession([
            'user_id'  => $user->id,
            'username' => $user->username,
            'role'     => $user->role,
        ]);
        $this->actingAs($user);

        return $user;
    }

    public function test_admin_can_access_menu_user(): void
    {
        $this->loginAs('admin');

        $this->get('/menu-user')
            ->assertOk()
            ->assertSee('Menu User');
    }

    public function test_siswa_cannot_access_menu_user(): void
    {
        $this->loginAs('siswa');

        $this->get('/menu-user')->assertStatus(403);
    }

    public function test_guest_redirected_from_menu_user(): void
    {
        $this->get('/menu-user')->assertRedirect('/login');
    }

    public function test_admin_can_view_detail_for_multiple_users(): void
    {
        $this->loginAs('admin');
        $admin = User::where('role', 'admin')->first();
        $siswa = User::where('role', 'siswa')->first();

        $this->get('/menu-user')
            ->assertOk()
            ->assertSee(route('users.show', $siswa));

        $this->get('/menu-user/'.$siswa->id)
            ->assertOk()
            ->assertSee('Detail User')
            ->assertSee($siswa->nama)
            ->assertSee($siswa->username)
            ->assertSee($siswa->email)
            ->assertSee(route('users.index'))
            ->assertDontSee($siswa->password);

        $this->get('/menu-user/'.$admin->id)
            ->assertOk()
            ->assertSee($admin->nama)
            ->assertSee($admin->username)
            ->assertSee($admin->email)
            ->assertDontSee($admin->password);
    }

    public function test_user_detail_returns_404_for_unknown_id(): void
    {
        $this->loginAs('admin');

        $this->get('/menu-user/999999')->assertNotFound();
        $this->get('/menu-user/id-tidak-valid')->assertNotFound();
    }

    public function test_siswa_cannot_view_user_detail(): void
    {
        $this->loginAs('siswa');
        $admin = User::where('role', 'admin')->first();

        $this->get('/menu-user/'.$admin->id)->assertStatus(403);
    }

    public function test_guest_redirected_from_user_detail(): void
    {
        $this->get('/menu-user/1')->assertRedirect('/login');
    }

    public function test_admin_can_create_user(): void
    {
        Storage::fake('local');
        $this->loginAs('admin');

        $this->post('/menu-user', [
            'username'              => 'userbaru',
            'email'                 => 'userbaru@tes.com',
            'nama'                  => 'User Baru',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'siswa',
            'status'                => 'Aktif',
            'nik'                   => '0012345678901234',
            'tempat_lahir'          => 'Bandung',
            'tanggal_lahir'         => '2007-09-17',
            'alamat'                => 'Jl. Contoh No. 1',
            'no_telepon'            => '081234567890',
            'asal_sekolah'          => 'SMK Negeri 1 Bandung',
            'alamat_sekolah'        => 'Jl. Sekolah No. 2',
            'jenis_identitas_sekolah' => 'NISN',
            'nomor_identitas_sekolah' => '0012345678',
            'pkl_mulai'             => '2026-09-17',
            'pkl_selesai'           => '2026-12-17',
            'ktp'                   => UploadedFile::fake()->image('ktp.jpg', 600, 400),
            'kartu_pelajar'         => UploadedFile::fake()->image('kartu.png', 600, 400),
        ])->assertRedirect('/menu-user');

        $this->assertDatabaseHas('users', [
            'username'                => 'userbaru',
            'nik'                     => '0012345678901234',
            'jenis_identitas_sekolah' => 'NISN',
            'nomor_identitas_sekolah' => '0012345678',
        ]);

        $created = User::where('username', 'userbaru')->first();
        $this->assertTrue(Hash::check('password123', $created->password));
        $this->assertNotNull($created->ktp_path);
        $this->assertNotNull($created->kartu_pelajar_path);
        $this->assertTrue(Storage::disk('local')->exists($created->ktp_path));
        $this->assertTrue(Storage::disk('local')->exists($created->kartu_pelajar_path));
    }

    public function test_admin_can_edit_user_without_changing_password(): void
    {
        $this->loginAs('admin');
        $user = User::where('username', 'siswa')->first();
        $oldPassword = $user->password;

        $this->put('/menu-user/'.$user->id, [
            'username' => 'siswa-updated',
            'email'    => 'siswa@tes.com',
            'nama'     => 'Siswa Diedit',
            'role'     => 'siswa',
            'status'   => 'Aktif',
        ])->assertRedirect('/menu-user');

        $user->refresh();
        $this->assertEquals('siswa-updated', $user->username);
        $this->assertEquals('Siswa Diedit', $user->nama);
        $this->assertEquals($oldPassword, $user->password);
    }

    public function test_admin_can_change_password(): void
    {
        $this->loginAs('admin');
        $user = User::where('username', 'siswa')->first();

        $this->put('/menu-user/'.$user->id.'/password', [
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect('/menu-user');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->loginAs('admin');

        $this->delete('/menu-user/'.$admin->id)
            ->assertRedirect('/menu-user');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_last_admin_cannot_be_deleted(): void
    {
        // Hapus admin buatan setUp, sisakan hanya satu admin.
        $this->loginAs('admin');
        $admin = User::where('role', 'admin')->first();

        $this->delete('/menu-user/'.$admin->id)
            ->assertRedirect('/menu-user');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_last_admin_cannot_be_downgraded_to_siswa(): void
    {
        $admin = $this->loginAs('admin');
        // Coba ubah admin terakhir menjadi siswa
        $response = $this->put('/menu-user/'.$admin->id, [
            'username' => $admin->username,
            'email'    => $admin->email,
            'nama'     => $admin->nama,
            'role'     => 'siswa',
            'status'   => 'Aktif',
            'user_id'  => 'G1 - INT0001',
        ]);
        $response->assertRedirect('/menu-user');
        $response->assertSessionHas('error');
        $admin->refresh();
        $this->assertEquals('admin', $admin->role);
    }

    public function test_admin_can_be_downgraded_when_multiple_admins(): void
    {
        // Buat admin kedua
        User::create([
            'username' => 'admin2',
            'email'    => 'admin2@tes.com',
            'nama'     => 'Admin Dua',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
            'status'   => 'Aktif',
        ]);
        $admin = User::where('username', 'admin')->first();
        $this->loginAs('admin');
        $response = $this->put('/menu-user/'.$admin->id, [
            'username' => $admin->username,
            'email'    => $admin->email,
            'nama'     => $admin->nama,
            'role'     => 'siswa',
            'status'   => 'Aktif',
            'user_id'  => 'G1 - INT0001',
        ]);
        $response->assertRedirect('/menu-user');
        $response->assertSessionHas('success');
        $admin->refresh();
        $this->assertEquals('siswa', $admin->role);
    }

    public function test_admin_can_delete_other_user(): void
    {
        $this->loginAs('admin');
        $siswa = User::where('username', 'siswa')->first();

        $this->delete('/menu-user/'.$siswa->id)
            ->assertRedirect('/menu-user');

        $this->assertDatabaseMissing('users', ['id' => $siswa->id]);
    }

    /**
     * UserSeeder harus membuat admin dengan role 'admin' dan status 'Aktif',
     * bukan default kolom 'siswa', agar akun hasil seeding bisa mengakses
     * halaman admin.
     */
    public function test_user_seeder_membuat_admin_dengan_role_admin(): void
    {
        // Hapus admin bawaan setUp agar seeder bisa create admin sendiri.
        User::where('username', 'admin')->delete();

        $this->seed(\Database\Seeders\UserSeeder::class);

        $admin = User::where('username', 'admin')->first();

        $this->assertNotNull($admin);
        $this->assertEquals('admin', $admin->role);
        $this->assertEquals('Aktif', $admin->status);
        $this->assertNotNull($admin->nama);
        $this->assertEquals('admin', $admin->username);
        $this->assertTrue(Hash::check('password123', $admin->password));
    }
}
