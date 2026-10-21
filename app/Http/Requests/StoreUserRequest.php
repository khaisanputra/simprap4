<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Hanya admin yang dapat membuat user baru
     * (validasi tambahan via middleware role pada route).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Jika role siswa dan user_id kosong, isi otomatis dengan nomor INT berikutnya
     * (misal max INT0042 → INT0043) agar pembuatan user baru tetap berlanjut.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('role') === 'siswa' && empty($this->input('user_id'))) {
            $next = $this->generateNextUserId();
            if ($next) {
                $this->merge(['user_id' => $next]);
            }
        }
    }

    /**
     * Generate User ID berikutnya (duplikasi logic dari UserController agar
     * validasi tetap lolos saat auto-fill). Tetap aman terhadap unique.
     */
    private function generateNextUserId(): ?string
    {
        try {
            $max = User::whereNotNull('user_id')
                ->pluck('user_id')
                ->filter(fn($val) => preg_match('/^G1\s*-\s*INT\d+$/', $val))
                ->map(function ($val) {
                    if (preg_match('/INT(\d+)/', $val, $m)) {
                        return (int) $m[1];
                    }
                    return 0;
                })->max();

            $nextNum = $max ? $max + 1 : 30;
            $attempt = $nextNum;
            for ($i = 0; $i < 100; $i++) {
                $candidate = 'G1 - INT' . str_pad((string) $attempt, 4, '0', STR_PAD_LEFT);
                if (!User::where('user_id', $candidate)->exists()) {
                    return $candidate;
                }
                $attempt++;
            }
            return 'G1 - INT' . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Aturan validasi untuk membuat user baru.
     */
    public function rules(): array
    {
        $wajibSiswa = Rule::requiredIf(fn () => $this->input('role') === 'siswa');

        return [
            'user_id'  => [
                'nullable',
                'string',
                'max:30',
                'regex:/^G1\s*-\s*INT\d{4,}$/',
                'unique:users,user_id',
                Rule::requiredIf(fn () => $this->input('role') === 'siswa'),
                'prohibited_if:role,admin',
            ],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'nama'     => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', 'string', Rule::in(['admin', 'siswa'])],
            'status'   => ['required', 'string', Rule::in(['Aktif', 'Nonaktif'])],

            'nik'                      => [$wajibSiswa, 'nullable', 'string', 'digits:16'],
            'tempat_lahir'             => [$wajibSiswa, 'nullable', 'string', 'max:100'],
            'tanggal_lahir'            => [$wajibSiswa, 'nullable', 'date', 'before_or_equal:today'],
            'alamat'                   => [$wajibSiswa, 'nullable', 'string', 'max:1000'],
            'no_telepon'               => [$wajibSiswa, 'nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'asal_sekolah'             => [$wajibSiswa, 'nullable', 'string', 'max:150'],
            'alamat_sekolah'           => [$wajibSiswa, 'nullable', 'string', 'max:1000'],
            'jenis_identitas_sekolah'  => [$wajibSiswa, 'nullable', Rule::in(['NISN', 'NRP', 'NIP'])],
            'nomor_identitas_sekolah'  => [$wajibSiswa, 'nullable', 'string', 'max:30', 'regex:/^[0-9A-Za-z.\-\/]+$/'],
            'pkl_mulai'                => [$wajibSiswa, 'nullable', 'date'],
            'pkl_selesai'              => [$wajibSiswa, 'nullable', 'date', 'after_or_equal:pkl_mulai'],
            'ktp'                      => [$wajibSiswa, 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'kartu_pelajar'            => [$wajibSiswa, 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * Pesan kesalahan dalam Bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'user_id.required'   => 'User ID wajib diisi untuk role Siswa.',
            'user_id.string'     => 'User ID harus berupa teks.',
            'user_id.max'        => 'User ID maksimal 30 karakter.',
            'user_id.regex'      => 'Format User ID tidak valid. Contoh: G1 - INT0030',
            'user_id.unique'     => 'User ID sudah digunakan.',
            'user_id.prohibited_if' => 'Admin tidak boleh memiliki User ID G1.',
            'username.required'  => 'Username wajib diisi.',
            'username.unique'    => 'Username sudah digunakan.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email sudah digunakan.',
            'nama.required'      => 'Nama wajib diisi.',
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'role.required'      => 'Role wajib dipilih.',
            'status.required'    => 'Status wajib dipilih.',

            'nik.required'                     => 'NIK wajib diisi untuk Siswa.',
            'nik.digits'                       => 'NIK harus terdiri dari 16 angka.',
            'tempat_lahir.required'            => 'Tempat lahir wajib diisi untuk Siswa.',
            'tanggal_lahir.required'           => 'Tanggal lahir wajib diisi untuk Siswa.',
            'tanggal_lahir.date'               => 'Tanggal lahir tidak valid.',
            'tanggal_lahir.before_or_equal'    => 'Tanggal lahir tidak boleh melewati hari ini.',
            'alamat.required'                  => 'Alamat lengkap wajib diisi untuk Siswa.',
            'no_telepon.required'              => 'Nomor telepon wajib diisi untuk Siswa.',
            'no_telepon.regex'                 => 'Format nomor telepon tidak valid.',
            'asal_sekolah.required'            => 'Asal sekolah wajib diisi untuk Siswa.',
            'alamat_sekolah.required'          => 'Alamat sekolah wajib diisi untuk Siswa.',
            'jenis_identitas_sekolah.required' => 'Jenis identitas sekolah wajib dipilih untuk Siswa.',
            'jenis_identitas_sekolah.in'       => 'Jenis identitas sekolah harus NISN, NRP, atau NIP.',
            'nomor_identitas_sekolah.required' => 'Nomor identitas sekolah wajib diisi untuk Siswa.',
            'nomor_identitas_sekolah.regex'    => 'Format nomor identitas sekolah tidak valid.',
            'pkl_mulai.required'               => 'Tanggal mulai PKL wajib diisi untuk Siswa.',
            'pkl_mulai.date'                   => 'Tanggal mulai PKL tidak valid.',
            'pkl_selesai.required'             => 'Tanggal selesai PKL wajib diisi untuk Siswa.',
            'pkl_selesai.date'                 => 'Tanggal selesai PKL tidak valid.',
            'pkl_selesai.after_or_equal'       => 'Tanggal selesai PKL tidak boleh sebelum tanggal mulai PKL.',
            'ktp.required'                     => 'Dokumen KTP wajib diunggah untuk Siswa.',
            'ktp.image'                        => 'Dokumen KTP harus berupa gambar.',
            'ktp.mimes'                        => 'Dokumen KTP harus berformat JPEG, PNG, atau WebP.',
            'ktp.max'                          => 'Ukuran dokumen KTP maksimal 2 MB.',
            'kartu_pelajar.required'           => 'Kartu pelajar wajib diunggah untuk Siswa.',
            'kartu_pelajar.image'              => 'Kartu pelajar harus berupa gambar.',
            'kartu_pelajar.mimes'              => 'Kartu pelajar harus berformat JPEG, PNG, atau WebP.',
            'kartu_pelajar.max'                => 'Ukuran kartu pelajar maksimal 2 MB.',
        ];
    }
}
