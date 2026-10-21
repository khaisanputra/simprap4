<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = User::create([
            'username' => 'admin',
            'email'    => 'admin@tes.com',
            'nama'     => 'Administrator',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
            'status'   => 'Aktif',
        ]);

        $this->siswa = User::create([
            'username' => 'siswa',
            'email'    => 'siswa@tes.com',
            'nama'     => 'Siswa Test',
            'password' => Hash::make('password123'),
            'role'     => 'siswa',
            'status'   => 'Aktif',
        ]);
    }

    protected function profilPayload(array $overrides = []): array
    {
        return array_merge([
            'nik'                     => '0012345678901234',
            'tempat_lahir'            => 'Bandung',
            'tanggal_lahir'           => '2007-09-17',
            'alamat'                  => 'Jl. Contoh No. 1, Bandung',
            'no_telepon'              => '081234567890',
            'asal_sekolah'            => 'SMK Negeri 1 Bandung',
            'alamat_sekolah'          => 'Jl. Sekolah No. 2, Bandung',
            'jenis_identitas_sekolah' => 'NISN',
            'nomor_identitas_sekolah' => '0012345678',
            'pkl_mulai'               => '2026-09-17',
            'pkl_selesai'             => '2026-12-17',
        ], $overrides);
    }

    protected function createPayload(array $overrides = []): array
    {
        return array_merge([
            'username'              => 'siswabaru',
            'email'                 => 'siswabaru@tes.com',
            'nama'                  => 'Siswa Baru',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'siswa',
            'status'                => 'Aktif',
            'ktp'                   => UploadedFile::fake()->image('ktp.jpg', 600, 400),
            'kartu_pelajar'         => UploadedFile::fake()->image('kartu.png', 600, 400),
        ], $this->profilPayload(), $overrides);
    }

    public function test_admin_can_create_user_with_full_profile_and_documents(): void
    {
        $this->actingAs($this->admin);

        $this->post('/menu-user', $this->createPayload())
            ->assertRedirect('/menu-user');

        $user = User::where('username', 'siswabaru')->firstOrFail();

        $this->assertSame('0012345678901234', $user->nik);
        $this->assertSame('0012345678', $user->nomor_identitas_sekolah);
        $this->assertSame('2007-09-17', $user->tanggal_lahir->format('Y-m-d'));
        $this->assertSame('2026-09-17', $user->pkl_mulai->format('Y-m-d'));
        $this->assertSame('2026-12-17', $user->pkl_selesai->format('Y-m-d'));
        $this->assertNotNull($user->ktp_path);
        $this->assertNotNull($user->kartu_pelajar_path);
        $this->assertStringStartsWith('dokumen-user/ktp/', $user->ktp_path);
        $this->assertStringStartsWith('dokumen-user/kartu-pelajar/', $user->kartu_pelajar_path);
        $this->assertTrue(Storage::disk('local')->exists($user->ktp_path));
        $this->assertTrue(Storage::disk('local')->exists($user->kartu_pelajar_path));

        $this->get('/menu-user/'.$user->id)
            ->assertOk()
            ->assertSee('Identitas Pribadi')
            ->assertSee('Informasi Sekolah')
            ->assertSee('Periode PKL')
            ->assertSee('Dokumen Identitas')
            ->assertSee('0012345678901234')
            ->assertSee('0012345678')
            ->assertSee('SMK Negeri 1 Bandung')
            ->assertSee('17 September 2007')
            ->assertSee('17 September 2026')
            ->assertSee('17 Desember 2026')
            ->assertSee(route('users.document', [$user, 'ktp']))
            ->assertSee(route('users.document', [$user, 'kartu-pelajar']))
            ->assertDontSee($user->password);

        $this->get('/menu-user/'.$user->id.'/dokumen/ktp')->assertOk();
        $this->get('/menu-user/'.$user->id.'/dokumen/kartu-pelajar')->assertOk();
    }

    public function test_edit_user_updates_profile_and_replaces_documents(): void
    {
        Storage::disk('local')->put('dokumen-user/ktp/lama.jpg', 'lama');
        Storage::disk('local')->put('dokumen-user/kartu-pelajar/lama.png', 'lama');

        $user = User::create(array_merge([
            'user_id'  => 'G1 - INT0099',
            'username' => 'siswalama',
            'email'    => 'siswalama@tes.com',
            'nama'     => 'Siswa Lama',
            'password' => Hash::make('password123'),
            'role'     => 'siswa',
            'status'   => 'Aktif',
            'ktp_path' => 'dokumen-user/ktp/lama.jpg',
            'kartu_pelajar_path' => 'dokumen-user/kartu-pelajar/lama.png',
        ], $this->profilPayload()));

        $this->actingAs($this->admin);

        $this->put('/menu-user/'.$user->id, [
            'username'                => 'siswalama',
            'email'                   => 'siswalama@tes.com',
            'nama'                    => 'Siswa Lama Diperbarui',
            'role'                    => 'siswa',
            'status'                  => 'Aktif',
            'user_id'                 => 'G1 - INT0099',
            'nik'                     => '0098765432109876',
            'tempat_lahir'            => 'Jakarta',
            'tanggal_lahir'           => '2006-01-02',
            'alamat'                  => 'Jl. Baru No. 10',
            'no_telepon'              => '089876543210',
            'asal_sekolah'            => 'SMK Negeri 2 Jakarta',
            'alamat_sekolah'          => 'Jl. Sekolah Baru No. 20',
            'jenis_identitas_sekolah' => 'NIP',
            'nomor_identitas_sekolah' => '001112223334',
            'pkl_mulai'               => '2026-10-01',
            'pkl_selesai'             => '2027-01-31',
            'ktp'                     => UploadedFile::fake()->image('ktp-baru.jpg', 600, 400),
            'kartu_pelajar'           => UploadedFile::fake()->image('kartu-baru.webp', 600, 400),
        ])->assertRedirect('/menu-user');

        $user->refresh();

        $this->assertSame('0098765432109876', $user->nik);
        $this->assertSame('NIP', $user->jenis_identitas_sekolah);
        $this->assertSame('001112223334', $user->nomor_identitas_sekolah);
        $this->assertSame('2026-10-01', $user->pkl_mulai->format('Y-m-d'));
        $this->assertSame('2027-01-31', $user->pkl_selesai->format('Y-m-d'));
        $this->assertNotSame('dokumen-user/ktp/lama.jpg', $user->ktp_path);
        $this->assertNotSame('dokumen-user/kartu-pelajar/lama.png', $user->kartu_pelajar_path);
        $this->assertFalse(Storage::disk('local')->exists('dokumen-user/ktp/lama.jpg'));
        $this->assertFalse(Storage::disk('local')->exists('dokumen-user/kartu-pelajar/lama.png'));
        $this->assertTrue(Storage::disk('local')->exists($user->ktp_path));
        $this->assertTrue(Storage::disk('local')->exists($user->kartu_pelajar_path));

        $this->get('/menu-user/'.$user->id)
            ->assertOk()
            ->assertSee('0098765432109876')
            ->assertSee('001112223334')
            ->assertSee('01 Oktober 2026')
            ->assertSee('31 Januari 2027');
    }

    public function test_invalid_document_type_is_rejected_by_server(): void
    {
        $this->actingAs($this->admin);

        $this->post('/menu-user', $this->createPayload([
            'ktp' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
        ]))->assertSessionHasErrors('ktp');

        $this->assertDatabaseMissing('users', ['username' => 'siswabaru']);
    }

    public function test_oversized_document_is_rejected_by_server(): void
    {
        $this->actingAs($this->admin);

        $this->post('/menu-user', $this->createPayload([
            'ktp' => UploadedFile::fake()->image('ktp.jpg', 600, 400)->size(3000),
        ]))->assertSessionHasErrors('ktp');

        $this->assertDatabaseMissing('users', ['username' => 'siswabaru']);
    }

    public function test_missing_profile_fields_are_rejected_for_siswa(): void
    {
        $this->actingAs($this->admin);

        $this->post('/menu-user', [
            'username'              => 'tanpaprofil',
            'email'                 => 'tanpaprofil@tes.com',
            'nama'                  => 'Tanpa Profil',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'siswa',
            'status'                => 'Aktif',
        ])->assertSessionHasErrors([
            'nik',
            'asal_sekolah',
            'jenis_identitas_sekolah',
            'nomor_identitas_sekolah',
            'pkl_mulai',
            'pkl_selesai',
            'ktp',
            'kartu_pelajar',
        ]);

        $this->assertDatabaseMissing('users', ['username' => 'tanpaprofil']);
    }

    public function test_guest_and_siswa_cannot_access_detail_or_private_documents(): void
    {
        Storage::disk('local')->put('dokumen-user/ktp/foto.jpg', 'bukan-gambar');
        $target = User::create(array_merge([
            'user_id'  => 'G1 - INT0098',
            'username' => 'target',
            'email'    => 'target@tes.com',
            'nama'     => 'Target',
            'password' => Hash::make('password123'),
            'role'     => 'siswa',
            'status'   => 'Aktif',
            'ktp_path' => 'dokumen-user/ktp/foto.jpg',
        ], $this->profilPayload()));

        $this->actingAs($this->siswa);
        $this->get('/menu-user/'.$target->id)->assertForbidden();
        $this->get('/menu-user/'.$target->id.'/dokumen/ktp')->assertForbidden();

        Auth::logout();
        $this->get('/menu-user/'.$target->id)->assertRedirect('/login');
        $this->get('/menu-user/'.$target->id.'/dokumen/ktp')->assertRedirect('/login');
    }

    public function test_unknown_user_id_returns_404(): void
    {
        $this->actingAs($this->admin);

        $this->get('/menu-user/999999')->assertNotFound();
        $this->get('/menu-user/999999/dokumen/ktp')->assertNotFound();
    }

    public function test_old_user_without_profile_still_renders_detail(): void
    {
        $this->actingAs($this->admin);

        $this->get('/menu-user/'.$this->siswa->id)
            ->assertOk()
            ->assertSee('Detail User')
            ->assertSee('Belum diisi')
            ->assertSee('Belum diunggah')
            ->assertSee('Identitas Pribadi')
            ->assertSee('Informasi Sekolah')
            ->assertSee('Periode PKL')
            ->assertSee('Dokumen Identitas');
    }

    public function test_admin_can_open_create_and_edit_forms_with_profile_fields(): void
    {
        $this->actingAs($this->admin);

        $this->get('/menu-user/create')
            ->assertOk()
            ->assertSee('Identitas Pribadi')
            ->assertSee('Informasi Sekolah')
            ->assertSee('Periode PKL')
            ->assertSee('Upload KTP')
            ->assertSee('Upload Kartu Pelajar');

        $this->get('/menu-user/'.$this->siswa->id.'/edit')
            ->assertOk()
            ->assertSee('Identitas Pribadi')
            ->assertSee('Informasi Sekolah')
            ->assertSee('Periode PKL')
            ->assertSee('Ganti KTP (opsional)')
            ->assertSee('Ganti Kartu Pelajar (opsional)');
    }

    public function test_admin_can_still_be_created_without_student_profile(): void
    {
        $this->actingAs($this->admin);

        $this->post('/menu-user', [
            'username'              => 'adminbaru',
            'email'                 => 'adminbaru@tes.com',
            'nama'                  => 'Admin Baru',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'admin',
            'status'                => 'Aktif',
        ])->assertRedirect('/menu-user');

        $this->assertDatabaseHas('users', [
            'username' => 'adminbaru',
            'role'     => 'admin',
            'nik'      => null,
            'ktp_path' => null,
        ]);
    }

    public function test_deleting_user_removes_private_documents(): void
    {
        $ktpPath = UploadedFile::fake()->image('ktp.jpg', 600, 400)
            ->store('dokumen-user/ktp', 'local');
        $kartuPath = UploadedFile::fake()->image('kartu.png', 600, 400)
            ->store('dokumen-user/kartu-pelajar', 'local');

        $user = User::create(array_merge([
            'user_id'  => 'G1 - INT0095',
            'username' => 'akandihapus',
            'email'    => 'akandihapus@tes.com',
            'nama'     => 'Akan Dihapus',
            'password' => Hash::make('password123'),
            'role'     => 'siswa',
            'status'   => 'Aktif',
            'ktp_path' => $ktpPath,
            'kartu_pelajar_path' => $kartuPath,
        ], $this->profilPayload()));

        $this->actingAs($this->admin);

        $this->delete('/menu-user/'.$user->id)->assertRedirect('/menu-user');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertFalse(Storage::disk('local')->exists($ktpPath));
        $this->assertFalse(Storage::disk('local')->exists($kartuPath));
    }

    public function test_document_route_only_serves_document_owned_by_requested_user(): void
    {
        $path = UploadedFile::fake()->image('ktp.jpg', 600, 400)
            ->store('dokumen-user/ktp', 'local');

        $userA = User::create(array_merge([
            'user_id'  => 'G1 - INT0096',
            'username' => 'usera',
            'email'    => 'usera@tes.com',
            'nama'     => 'User A',
            'password' => Hash::make('password123'),
            'role'     => 'siswa',
            'status'   => 'Aktif',
            'ktp_path' => $path,
        ], $this->profilPayload()));

        $userB = User::create(array_merge([
            'user_id'  => 'G1 - INT0097',
            'username' => 'userb',
            'email'    => 'userb@tes.com',
            'nama'     => 'User B',
            'password' => Hash::make('password123'),
            'role'     => 'siswa',
            'status'   => 'Aktif',
        ], $this->profilPayload()));

        $this->actingAs($this->admin);

        $this->get('/menu-user/'.$userA->id.'/dokumen/ktp')->assertOk();
        $this->get('/menu-user/'.$userB->id.'/dokumen/ktp')->assertNotFound();
        $this->get('/menu-user/'.$userA->id.'/dokumen/lainnya')->assertNotFound();
    }
}
