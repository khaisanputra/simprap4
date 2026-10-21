<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Data sesi admin aktif - hanya authenticated Laravel user (Tahap 4.2).
     */
    protected function userSession(Request $request): array
    {
        $user = $request->user() ?? \Illuminate\Support\Facades\Auth::user();
        $role = $user?->role ?? 'siswa';
        return [
            'username' => $user?->username ?? $user?->nama ?? null,
            'role'     => $role,
            'user_id'  => $user?->id,
        ];
    }

    protected function dataProfil(Request $request): array
    {
        $nilai = function (string $key) use ($request) {
            return $request->filled($key) ? trim((string) $request->input($key)) : null;
        };

        return [
            'nik'                     => $nilai('nik'),
            'tempat_lahir'            => $nilai('tempat_lahir'),
            'tanggal_lahir'           => $nilai('tanggal_lahir'),
            'alamat'                  => $nilai('alamat'),
            'no_telepon'              => $nilai('no_telepon'),
            'asal_sekolah'            => $nilai('asal_sekolah'),
            'alamat_sekolah'          => $nilai('alamat_sekolah'),
            'jenis_identitas_sekolah' => $nilai('jenis_identitas_sekolah'),
            'nomor_identitas_sekolah' => $nilai('nomor_identitas_sekolah'),
            'pkl_mulai'               => $nilai('pkl_mulai'),
            'pkl_selesai'             => $nilai('pkl_selesai'),
        ];
    }

    protected function simpanDokumen(UploadedFile $file, string $jenis): string
    {
        $path = $file->store('dokumen-user/'.$jenis, 'local');

        if (! $path) {
            throw new \RuntimeException('Dokumen gagal disimpan.');
        }

        return $path;
    }

    protected function hapusDokumen(?string ...$paths): void
    {
        $paths = array_values(array_filter($paths));

        if ($paths !== []) {
            Storage::disk('local')->delete($paths);
        }
    }

    /**
     * Menampilkan daftar seluruh user dengan pencarian, filter role,
     * sorting, pagination, dan informasi jumlah total.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Pencarian berdasarkan User ID / nama / username / email.
        if ($cari = $request->query('cari')) {
            $query->where(function ($q) use ($cari) {
                $q->where('user_id', 'like', "%{$cari}%")
                    ->orWhere('nama', 'like', "%{$cari}%")
                    ->orWhere('username', 'like', "%{$cari}%")
                    ->orWhere('email', 'like', "%{$cari}%");
            });
        }

        // Filter berdasarkan role.
        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        // Sorting: kolom & arah di-whitelist agar aman.
        $sortable = ['user_id', 'nama', 'username', 'email', 'role', 'status', 'created_at'];
        $column   = in_array($request->query('sort'), $sortable) ? $request->query('sort') : 'created_at';
        $order    = ($request->query('order') === 'asc') ? 'asc' : 'desc';
        $query->orderBy($column, $order);

$users = $query->paginate(10)->withQueryString();
        $totalUsers = User::count();
        $userSession = $this->userSession($request);

        return view('users.index', compact('users', 'totalUsers', 'userSession'));
    }

    /**
     * Menampilkan form tambah user baru.
     */
    public function create(Request $request)
    {
        $userSession = $this->userSession($request);

        // Hitung User ID berikutnya otomatis (G1 - INT terakhir + 1) untuk membantu admin
        $nextUserId = $this->generateNextUserId();

        return view('users.create', compact('userSession', 'nextUserId'));
    }

    /**
     * Generate User ID berikutnya berdasarkan nomor INT terakhir.
     * Format: G1 - INTxxxx, contoh G1 - INT0043
     */
    protected function generateNextUserId(): ?string
    {
        try {
            // Ambil semua user_id lalu filter via PHP agar kompatibel MySQL & SQLite (testing)
            $max = User::whereNotNull('user_id')
                ->pluck('user_id')
                ->filter(fn($val) => preg_match('/^G1\s*-\s*INT\d+$/', $val))
                ->map(function ($val) {
                    if (preg_match('/INT(\d+)/', $val, $m)) {
                        return (int) $m[1];
                    }
                    return 0;
                })->max();

            // Fallback jika tidak ada data, mulai dari INT0030 (koreksi dari INT0031)
            // Tapi jangan paksa INT0030 jika sudah terpakai, tetap ambil max + 1
            $nextNum = $max ? $max + 1 : 30;
            // Pastikan tidak bentrok dengan yang sudah ada (loop sampai unik)
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
     * Menyimpan user baru ke database dengan password ter-hash.
     */
    public function store(StoreUserRequest $request)
    {
        // Admin tidak boleh memiliki User ID G1 — paksa null
        // Jika siswa dan user_id masih kosong (misal JS disabled), generate otomatis INT berikutnya
        $userIdValue = $request->role === 'admin' ? null : ($request->user_id ? trim($request->user_id) : null);
        if ($request->role === 'siswa' && empty($userIdValue)) {
            $userIdValue = $this->generateNextUserId();
        }

        $ktpPath = null;
        $kartuPelajarPath = null;

        try {
            if ($request->hasFile('ktp')) {
                $ktpPath = $this->simpanDokumen($request->file('ktp'), 'ktp');
            }

            if ($request->hasFile('kartu_pelajar')) {
                $kartuPelajarPath = $this->simpanDokumen($request->file('kartu_pelajar'), 'kartu-pelajar');
            }

            User::create(array_merge([
                'user_id'  => $userIdValue,
                'username' => $request->username,
                'email'    => $request->email,
                'nama'     => $request->nama,
                'password' => Hash::make($request->password),
                'role'     => $request->role,
                'status'   => $request->status,
            ], $this->dataProfil($request), [
                'ktp_path'           => $ktpPath,
                'kartu_pelajar_path' => $kartuPelajarPath,
            ]));
        } catch (\Throwable $e) {
            $this->hapusDokumen($ktpPath, $kartuPelajarPath);
            throw $e;
        }

        $byUser = $request->user() ?? \Illuminate\Support\Facades\Auth::user();
        Log::info('Admin menambahkan user baru', [
            'by'       => $byUser?->username ?? $byUser?->nama ?? 'system',
            'username' => $request->username,
            'user_id'  => $userIdValue,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail data user.
     */
    public function show(Request $request, User $user)
    {
        $userSession = $this->userSession($request);

        return view('users.show', compact('user', 'userSession'));
    }

    /**
     * Menampilkan dokumen identitas user dari storage privat.
     */
    public function document(Request $request, User $user, string $jenis)
    {
        if (($request->user()?->role ?? null) !== 'admin') {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        $dokumen = [
            'ktp'           => $user->ktp_path,
            'kartu-pelajar' => $user->kartu_pelajar_path,
        ];

        if (! array_key_exists($jenis, $dokumen)) {
            abort(404);
        }

        $path = $dokumen[$jenis];

        if (! $path || ! str_starts_with($path, 'dokumen-user/') || ! Storage::disk('local')->exists($path)) {
            abort(404, 'Dokumen tidak ditemukan atau file sudah tidak tersedia.');
        }

        $mime = Storage::disk('local')->mimeType($path);

        if (! in_array($mime, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'], true)) {
            abort(403, 'Tipe dokumen tidak diizinkan.');
        }

        return Storage::disk('local')->response($path, null, [
            'Content-Type'           => $mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Menampilkan form edit data user.
     */
public function edit(Request $request, User $user)
    {
        $userSession = $this->userSession($request);

        return view('users.edit', compact('user', 'userSession'));
    }

    /**
     * Menyimpan perubahan data user (tanpa mengubah password).
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        // Lindungi admin terakhir: tidak boleh ubah role admin terakhir menjadi siswa
        if ($user->role === 'admin' && $request->role !== 'admin') {
            $totalAdmin = User::where('role', 'admin')->count();
            if ($totalAdmin <= 1) {
                return redirect()->route('users.index')
                    ->with('error', 'Tidak dapat mengubah role admin terakhir. Sistem harus memiliki minimal 1 admin.');
            }
        }

        // Admin tidak boleh memiliki User ID G1 — paksa null, siswa wajib ada user_id
        $userIdValue = $request->role === 'admin' ? null : ($request->user_id ? trim($request->user_id) : $user->user_id);

        $profilData = $request->role === 'siswa' ? $this->dataProfil($request) : [];
        $oldKtp = $user->ktp_path;
        $oldKartuPelajar = $user->kartu_pelajar_path;
        $newKtp = null;
        $newKartuPelajar = null;

        try {
            if ($request->hasFile('ktp')) {
                $newKtp = $this->simpanDokumen($request->file('ktp'), 'ktp');
            }

            if ($request->hasFile('kartu_pelajar')) {
                $newKartuPelajar = $this->simpanDokumen($request->file('kartu_pelajar'), 'kartu-pelajar');
            }

            $user->update(array_merge([
                'user_id'  => $userIdValue,
                'username' => $request->username,
                'email'    => $request->email,
                'nama'     => $request->nama,
                'role'     => $request->role,
                'status'   => $request->status,
            ], $profilData, [
                'ktp_path'           => $newKtp ?? $oldKtp,
                'kartu_pelajar_path' => $newKartuPelajar ?? $oldKartuPelajar,
            ]));
        } catch (\Throwable $e) {
            $this->hapusDokumen($newKtp, $newKartuPelajar);
            throw $e;
        }

        $this->hapusDokumen(
            $newKtp && $newKtp !== $oldKtp ? $oldKtp : null,
            $newKartuPelajar && $newKartuPelajar !== $oldKartuPelajar ? $oldKartuPelajar : null
        );

        $byUser2 = $request->user() ?? \Illuminate\Support\Facades\Auth::user();
        Log::info('Admin mengubah data user', [
            'by' => $byUser2?->username ?? $byUser2?->nama ?? 'system',
            'user_id' => $user->id,
            'updated_user_id' => $userIdValue,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Data user berhasil diperbarui.');
    }

    /**
     * Menghapus user dengan aturan:
     * - Tidak boleh menghapus admin yang sedang login.
     * - Harus tersisa minimal satu admin di sistem.
     */
    public function destroy(Request $request, User $user)
    {
        $loggedInId = ($request->user() ?? \Illuminate\Support\Facades\Auth::user())?->id ?? \Illuminate\Support\Facades\Auth::id();

        // Tidak boleh menghapus akun sendiri (admin yang sedang login).
        if ($user->id === (int) $loggedInId) {
            return redirect()->route('users.index')
                ->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan.');
        }

        // Jika user target adalah admin dan hanya tersisa satu admin, jangan hapus.
        if ($user->role === 'admin') {
            $totalAdmin = User::where('role', 'admin')->count();
            if ($totalAdmin <= 1) {
                return redirect()->route('users.index')
                    ->with('error', 'Tidak dapat menghapus admin terakhir di sistem.');
            }
        }

        // Cegah hapus user dengan data historis (tanpa ubah schema, cek aplikasi)
        // Jika memiliki attendance/laporan/laporan_sales/statistik, tolak hapus
        $hasHistory = \App\Models\Attendance::where('user_id', $user->id)->exists()
            || \App\Models\Laporan::where('user_id', $user->id)->exists()
            || \App\Models\LaporanSales::where('user_id', $user->id)->exists()
            || \App\Models\MonthlyStatistic::where('user_id', $user->id)->exists();
        // Fallback legacy nama untuk attendance tanpa user_id (data lama)
        if (!$hasHistory && !empty($user->nama)) {
            $hasHistory = \App\Models\Attendance::whereNull('user_id')->where('nama', $user->nama)->exists();
        }
        if ($hasHistory) {
            return redirect()->route('users.index')
                ->with('error', 'User tidak dapat dihapus karena memiliki data historis (absensi/laporan/rekap). Nonaktifkan status menjadi Nonaktif terlebih dahulu.');
        }

        $ktpPath = $user->ktp_path;
        $kartuPelajarPath = $user->kartu_pelajar_path;

        try {
            $user->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            // FK constraint (attendances/laporans/laporan_sales) — tampilkan pesan ramah, bukan 500
            if (str_contains($e->getMessage(), '1451') || str_contains($e->getMessage(), 'FOREIGN KEY') || str_contains($e->getMessage(), 'constraint')) {
                return redirect()->route('users.index')
                    ->with('error', 'User tidak dapat dihapus karena masih memiliki data absen/laporan terkait. Nonaktifkan status menjadi Nonaktif terlebih dahulu.');
            }
            throw $e;
        }

        $this->hapusDokumen($ktpPath, $kartuPelajarPath);

        $byUser3 = $request->user() ?? \Illuminate\Support\Facades\Auth::user();
        Log::info('Admin menghapus user', [
            'by' => $byUser3?->username ?? $byUser3?->nama ?? 'system',
            'user_id' => $user->id,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User berhasil dihapus.');
    }

 /**
      * Menyimpan password baru (oleh admin, tanpa password lama).
      */
    public function updatePassword(ChangePasswordRequest $request, User $user)
    {
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $byUser4 = $request->user() ?? \Illuminate\Support\Facades\Auth::user();
        Log::info('Admin mengganti password user', [
            'by' => $byUser4?->username ?? $byUser4?->nama ?? 'system',
            'user_id' => $user->id,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Password user berhasil diganti.');
    }
}
