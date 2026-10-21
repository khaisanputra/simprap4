<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Hanya admin yang dapat mengubah data user.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Jika role siswa dan user_id tidak dikirim saat update, gunakan user_id
     * yang sudah ada pada record agar validasi tidak gagal dan data tetap konsisten.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('role') === 'siswa' && empty($this->input('user_id'))) {
            $routeUser = $this->route('user');
            $existingUserId = null;
            if ($routeUser instanceof User) {
                $existingUserId = $routeUser->user_id;
            } elseif (is_numeric($routeUser)) {
                $existingUserId = User::where('id', $routeUser)->value('user_id');
            } elseif (is_array($routeUser) && isset($routeUser['user_id'])) {
                $existingUserId = $routeUser['user_id'];
            }
            if (!empty($existingUserId)) {
                $this->merge(['user_id' => $existingUserId]);
            } else {
                // Jika existing juga null (data lama), generate berikutnya
                $next = $this->generateNextUserId();
                if ($next) {
                    $this->merge(['user_id' => $next]);
                }
            }
        }
    }

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
     * Aturan validasi untuk mengubah data user (tanpa password).
     */
    public function rules(): array
    {
        return [
            'user_id'  => [
                'nullable',
                'string',
                'max:30',
                'regex:/^G1\s*-\s*INT\d{4,}$/',
                Rule::unique('users', 'user_id')->ignore($this->route('user')),
                Rule::requiredIf(fn () => $this->input('role') === 'siswa'),
                'prohibited_if:role,admin',
            ],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->route('user'))],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'nama'     => ['required', 'string', 'max:255'],
            'role'     => ['required', 'string', Rule::in(['admin', 'siswa'])],
            'status'   => ['required', 'string', Rule::in(['Aktif', 'Nonaktif'])],

            'nik'                      => ['nullable', 'string', 'digits:16'],
            'tempat_lahir'             => ['nullable', 'string', 'max:100'],
            'tanggal_lahir'            => ['nullable', 'date', 'before_or_equal:today'],
            'alamat'                   => ['nullable', 'string', 'max:1000'],
            'no_telepon'               => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'asal_sekolah'             => ['nullable', 'string', 'max:150'],
            'alamat_sekolah'           => ['nullable', 'string', 'max:1000'],
            'jenis_identitas_sekolah'  => ['nullable', 'required_with:nomor_identitas_sekolah', Rule::in(['NISN', 'NRP', 'NIP'])],
            'nomor_identitas_sekolah'  => ['nullable', 'required_with:jenis_identitas_sekolah', 'string', 'max:30', 'regex:/^[0-9A-Za-z.\-\/]+$/'],
            'pkl_mulai'                => ['nullable', 'date', 'required_with:pkl_selesai'],
            'pkl_selesai'              => ['nullable', 'date', 'required_with:pkl_mulai', 'after_or_equal:pkl_mulai'],
            'ktp'                      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'kartu_pelajar'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
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
            'username.required' => 'Username wajib diisi.',
            'username.unique'   => 'Username sudah digunakan.',
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email sudah digunakan.',
            'nama.required'     => 'Nama wajib diisi.',
            'role.required'     => 'Role wajib dipilih.',
            'status.required'   => 'Status wajib dipilih.',

            'nik.digits'                       => 'NIK harus terdiri dari 16 angka.',
            'tanggal_lahir.date'               => 'Tanggal lahir tidak valid.',
            'tanggal_lahir.before_or_equal'    => 'Tanggal lahir tidak boleh melewati hari ini.',
            'no_telepon.regex'                 => 'Format nomor telepon tidak valid.',
            'jenis_identitas_sekolah.required_with' => 'Jenis identitas sekolah wajib dipilih jika nomor identitas diisi.',
            'jenis_identitas_sekolah.in'       => 'Jenis identitas sekolah harus NISN, NRP, atau NIP.',
            'nomor_identitas_sekolah.required_with' => 'Nomor identitas sekolah wajib diisi jika jenis identitas dipilih.',
            'nomor_identitas_sekolah.regex'    => 'Format nomor identitas sekolah tidak valid.',
            'pkl_mulai.required_with'          => 'Tanggal mulai PKL wajib diisi jika tanggal selesai diisi.',
            'pkl_mulai.date'                   => 'Tanggal mulai PKL tidak valid.',
            'pkl_selesai.required_with'        => 'Tanggal selesai PKL wajib diisi jika tanggal mulai diisi.',
            'pkl_selesai.date'                 => 'Tanggal selesai PKL tidak valid.',
            'pkl_selesai.after_or_equal'       => 'Tanggal selesai PKL tidak boleh sebelum tanggal mulai PKL.',
            'ktp.image'                        => 'Dokumen KTP harus berupa gambar.',
            'ktp.mimes'                        => 'Dokumen KTP harus berformat JPEG, PNG, atau WebP.',
            'ktp.max'                          => 'Ukuran dokumen KTP maksimal 2 MB.',
            'kartu_pelajar.image'              => 'Kartu pelajar harus berupa gambar.',
            'kartu_pelajar.mimes'              => 'Kartu pelajar harus berformat JPEG, PNG, atau WebP.',
            'kartu_pelajar.max'                => 'Ukuran kartu pelajar maksimal 2 MB.',
        ];
    }
}
