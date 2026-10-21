<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Models\User;
use App\Support\ImageCompressor;
use App\Support\WaktuAbsen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LaporanSalesFormController extends Controller
{
    protected function resolveUserId(Request $request): ?int
    {
        $user = $request->user() ?? Auth::user();
        if ($user) {
            return (int) $user->id;
        }
        return Auth::id() !== null ? (int) Auth::id() : null;
    }

    protected function resolveRole(Request $request): string
    {
        $user = $request->user() ?? Auth::user();
        return $user?->role ?? 'siswa';
    }

    protected function resolveUser(Request $request): ?User
    {
        return $request->user() ?? Auth::user();
    }

    protected function userSession(Request $request): array
    {
        $user = $this->resolveUser($request);
        return [
            'username' => $user?->username ?? $user?->nama ?? 'User',
            'role'     => $this->resolveRole($request),
            'user_id'  => $user?->id,
        ];
    }

    /**
     * Tahap 1 — Form Laporan Sales (HANYA form, tanpa Data Sales)
     * Mirip Teknisi: hanya siapkan data form (identitas, jam, userSession)
     */
    public function index(Request $request)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return redirect()->route('login');
        }
        $role = $this->resolveRole($request);
        $isAdmin = $role === 'admin';

        $userSession = $this->userSession($request);

        $jamLaporan = WaktuAbsen::all('laporan');
        $jamDibuka = WaktuAbsen::dibuka(now(), 'laporan');

        $identitas = [
            'nama' => $user->nama ?? $user->username,
            'username' => $user->username,
            'nis' => $user->nis ?? $user->nisn ?? $user->NIS ?? $user->NISN ?? null,
            'kelas' => $user->kelas ?? $user->kelas_jurusan ?? null,
            'jurusan' => $user->jurusan ?? null,
            'email' => $user->email ?? null,
            'tanggal_hari_ini' => now()->translatedFormat('d F Y'),
        ];

        // Daftar "Laporan Sales Saya" — dari laporans (jenis=sales, milik user login).
        // Status collect ditentukan dari relasi laporan_sales (tanpa kolom baru, tanpa dummy).
        // Keamanan: filter user_id = login, authorization per-laporan tetap dicek di Collect controller.
        $laporanSalesSaya = collect();
        $laporanHariIni = null;
        if (!$isAdmin) {
            $laporanSalesSaya = Laporan::query()
                ->with(['fotos'])
                ->withCount('sales')
                ->where('user_id', $user->id)
                ->where('jenis_laporan', Laporan::JENIS_SALES)
                ->orderByDesc('tanggal_laporan')
                ->orderByDesc('id')
                ->paginate(10, ['*'], 'sales_page')
                ->withQueryString();

            // Laporan utama APAPUN (teknisi/sales) milik siswa hari ini — untuk
            // pembatasan 1 laporan per hari lintas jenis. Zona waktu Asia/Jakarta.
            $laporanHariIni = Laporan::query()
                ->where('user_id', $user->id)
                ->whereDate('tanggal_laporan', now()->toDateString())
                ->orderBy('id')
                ->first();
        }

        return view('laporan.sales.index', compact(
            'userSession', 'user', 'identitas', 'isAdmin',
            'jamLaporan', 'jamDibuka', 'laporanSalesSaya', 'laporanHariIni'
        ));
    }

    /**
     * Simpan laporan utama Sales ke tabel laporans (Tahap 1)
     * Field: teks_laporan + fotos[] (1-6, max 10MB)
     * Setelah sukses redirect ke Collect Data Sales
     */
    public function store(Request $request)
    {
        if ($this->resolveRole($request) === 'admin') {
            return redirect()->route('laporan.sales.index')->with('sales_error', 'Admin tidak dapat membuat laporan Sales. Fitur ini hanya untuk Siswa.');
        }

        $user = $this->resolveUser($request);
        if (!$user) {
            return redirect()->route('login')->with('sales_error', 'Sesi tidak valid. Silakan login kembali.');
        }

        // Validasi jam laporan (08:00-23:59) — sama dengan Teknisi
        $now = now();
        if (!WaktuAbsen::dibuka($now, 'laporan')) {
            $jam = WaktuAbsen::all('laporan');
            $status = WaktuAbsen::status($now, 'laporan');
            if ($status === 'belum') {
                return back()->withInput()->with('sales_error', 'Laporan Sales belum dibuka. Jam laporan: ' . $jam['mulai'] . ' - ' . $jam['selesai'] . ' WIB.');
            } else {
                return back()->withInput()->with('sales_error', 'Jam laporan telah berakhir. Jam laporan: ' . $jam['mulai'] . ' - ' . $jam['selesai'] . ' WIB.');
            }
        }

        $validated = $request->validate([
            'teks_laporan' => ['required', 'string', 'min:10', 'max:10000'],
            'fotos'        => ['required', 'array', 'min:1', 'max:6'],
            'fotos.*'      => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'teks_laporan.required' => 'Teks laporan wajib diisi.',
            'teks_laporan.min'      => 'Teks laporan minimal 10 karakter.',
            'fotos.required'        => 'Foto dokumentasi wajib diupload. Minimal 1 foto.',
            'fotos.min'             => 'Minimal 1 foto wajib diupload.',
            'fotos.max'             => 'Maksimal 6 foto dapat diunggah untuk satu laporan.',
            'fotos.*.required'      => 'Setiap foto wajib diupload.',
            'fotos.*.image'         => 'File harus berupa gambar.',
            'fotos.*.mimes'         => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
            'fotos.*.max'           => 'Ukuran foto terlalu besar. Maksimal ukuran foto adalah 10 MB.',
        ]);

        $files = $request->file('fotos');
        if (!$files || count($files) < 1 || count($files) > 6) {
            return back()->withInput()->with('sales_error', 'Maksimal 6 foto dapat diunggah untuk satu laporan.');
        }

        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        foreach ($files as $f) {
            $mime = strtolower($f->getMimeType() ?? $f->getClientMimeType() ?? '');
            if (empty($mime)) {
                $mime = strtolower(@mime_content_type($f->getPathname()) ?: '');
            }
            if (!in_array($mime, $allowedMimes, true)) {
                $ext = strtolower($f->getClientOriginalExtension());
                if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
                    return back()->withInput()->with('sales_error', 'Tipe file foto tidak diizinkan. Gunakan JPG, JPEG, PNG, atau WEBP.');
                }
            }
            if (@getimagesize($f->getPathname()) === false) {
                return back()->withInput()->with('sales_error', 'File foto harus berupa gambar yang valid.');
            }
            if ($f->getSize() !== null && $f->getSize() > ImageCompressor::MAX_SIZE_BYTES) {
                return back()->withInput()->with('sales_error', ImageCompressor::maxSizeMessage());
            }
        }

        // Validasi kehadiran hari ini: harus sudah Absen Masuk — cek setelah validasi file agar error foto tetap prioritas
        $today = $now->toDateString();
        $attendanceHariIni = \App\Models\Attendance::query()
            ->padaTanggal($today)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere(function ($q2) use ($user) {
                      $q2->whereNull('user_id')->where('nama', $user->nama);
                  });
            })
            ->latest('id')
            ->first();
        if (! $attendanceHariIni || ! $attendanceHariIni->jam_masuk) {
            return back()->withInput()->with('sales_error', 'Anda harus melakukan Absen Masuk terlebih dahulu sebelum membuat laporan. Silakan lakukan Absen Masuk hari ini.');
        }

        // Pembatasan 1 laporan utama per siswa per hari kalender (SEMUA jenis).
        $tanggal = $today;
        $existingHariIni = Laporan::query()
            ->where('user_id', $user->id)
            ->whereDate('tanggal_laporan', $tanggal)
            ->orderBy('id')
            ->first();
        if ($existingHariIni) {
            return redirect()->route('laporan.show', $existingHariIni->id)
                ->with('sales_error', 'Laporan hari ini sudah dibuat. Silakan edit laporan yang tersedia.');
        }

        $storedPaths = [];
        DB::beginTransaction();
        try {
            // Cek ulang dengan lock untuk race condition (lihat LaporanController).
            $duplikatTerkunci = Laporan::query()
                ->where('user_id', $user->id)
                ->whereDate('tanggal_laporan', $tanggal)
                ->lockForUpdate()
                ->first();
            if ($duplikatTerkunci) {
                DB::rollBack();
                return redirect()->route('laporan.show', $duplikatTerkunci->id)
                    ->with('sales_error', 'Laporan hari ini sudah dibuat. Silakan edit laporan yang tersedia.');
            }

            $laporan = Laporan::create([
                'user_id'         => $user->id,
                'tanggal_laporan' => $tanggal,
                'teks_laporan'    => trim($validated['teks_laporan']),
                'jenis_laporan'   => Laporan::JENIS_SALES,
            ]);

            foreach ($files as $idx => $file) {
                try {
                    $fotoPath = ImageCompressor::compressAndStore($file, 'laporan/foto');
                } catch (\RuntimeException $e) {
                    throw new \RuntimeException($e->getMessage());
                }
                $storedPaths[] = $fotoPath;
                $mimeSimpan = str_ends_with(strtolower($fotoPath), '.webp') ? 'image/webp' : 'image/jpeg';
                LaporanFoto::create([
                    'laporan_id'         => $laporan->id,
                    'foto'               => $fotoPath,
                    'foto_original_name' => $file->getClientOriginalName(),
                    'foto_mime_type'     => $mimeSimpan,
                    'sort_order'         => $idx,
                ]);
                if ($idx === 0) {
                    $laporan->update([
                        'foto'               => $fotoPath,
                        'foto_original_name' => $file->getClientOriginalName(),
                        'foto_mime_type'     => $mimeSimpan,
                    ]);
                }
            }

            DB::commit();
            // Tandai laporan ini sebagai parent Sales yang sah untuk collect (non-schema, via session)
            // Ini memungkinkan first collect untuk parent yang baru dibuat dengan 0 sales
            $request->session()->put('sales_parent_'.$laporan->id, true);
        } catch (\RuntimeException $e) {
            DB::rollBack();
            foreach ($storedPaths as $p) {
                Storage::disk('public')->delete($p);
            }
            $msg = $e->getMessage();
            if (str_contains($msg, '10 MB') || str_contains($msg, 'Format foto') || str_contains($msg, 'gambar yang valid')) {
                return back()->withInput()->with('sales_error', $msg);
            }
            Log::error('Gagal menyimpan laporan Sales tahap 1', ['user_id' => $user->id, 'msg' => $msg]);
            return back()->withInput()->with('sales_error', $msg ?: 'Gagal menyimpan laporan Sales. Silakan coba lagi.');
        } catch (\Throwable $e) {
            DB::rollBack();
            foreach ($storedPaths as $p) {
                Storage::disk('public')->delete($p);
            }
            Log::error('Gagal menyimpan laporan Sales tahap 1', ['user_id' => $user->id, 'msg' => $e->getMessage()]);
            return back()->withInput()->with('sales_error', 'Gagal menyimpan laporan Sales. Silakan coba lagi.');
        }

        return redirect()->route('laporan.sales.collect', $laporan->id)->with('sales_success', 'Laporan Sales berhasil disimpan. Silakan lengkapi data customer (1–10 data).');
    }

    /**
     * Form edit laporan utama Sales — data sebelumnya ditampilkan kembali.
     * Data collect customer (laporan_sales) TIDAK diubah di sini.
     */
    public function edit(Request $request, Laporan $laporan)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return redirect()->route('login');
        }
        $role = $this->resolveRole($request);
        $isAdmin = $role === 'admin';

        if (!$isAdmin && (int) $laporan->user_id !== (int) $user->id) {
            abort(403, 'Anda tidak berhak mengedit laporan ini.');
        }

        if ($laporan->jenis_laporan !== Laporan::JENIS_SALES) {
            abort(403, 'Laporan ini bukan laporan Sales.');
        }

        $laporan->load(['user', 'fotos', 'sales']);
        $userSession = $this->userSession($request);

        return view('laporan.sales.edit', compact('userSession', 'user', 'laporan', 'isAdmin'));
    }

    /**
     * Perbarui laporan utama Sales — record yang sama (ID, pemilik, tanggal,
     * dan laporan_id collect tidak berubah). Foto baru opsional, ditambahkan
     * ke foto lama (total maksimal 6). Tidak menggandakan data customer.
     */
    public function update(Request $request, Laporan $laporan)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return redirect()->route('login');
        }
        $role = $this->resolveRole($request);
        $isAdmin = $role === 'admin';

        if (!$isAdmin && (int) $laporan->user_id !== (int) $user->id) {
            abort(403, 'Anda tidak berhak mengedit laporan ini.');
        }

        if ($laporan->jenis_laporan !== Laporan::JENIS_SALES) {
            abort(403, 'Laporan ini bukan laporan Sales.');
        }

        $validated = $request->validate([
            'teks_laporan' => ['required', 'string', 'min:10', 'max:10000'],
            'fotos'        => ['nullable', 'array', 'max:6'],
            'fotos.*'      => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'teks_laporan.required' => 'Teks laporan wajib diisi.',
            'teks_laporan.min'      => 'Teks laporan minimal 10 karakter.',
            'fotos.max'             => 'Maksimal 6 foto dapat diunggah untuk satu laporan.',
            'fotos.*.required'      => 'Setiap foto wajib diupload.',
            'fotos.*.image'         => 'File harus berupa gambar.',
            'fotos.*.mimes'         => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
            'fotos.*.max'           => 'Ukuran foto terlalu besar. Maksimal ukuran foto adalah 10 MB.',
        ]);

        $files = $request->file('fotos', []);
        if (!is_array($files)) {
            $files = [$files];
        }
        $files = array_values(array_filter($files));

        $laporan->loadMissing('fotos');
        $jumlahFotoLama = $laporan->fotos->count();
        if (count($files) > 0 && $jumlahFotoLama + count($files) > 6) {
            return back()->withInput()->with('sales_error', 'Maksimal 6 foto per laporan. Laporan ini sudah memiliki ' . $jumlahFotoLama . ' foto, Anda mencoba menambah ' . count($files) . ' lagi.');
        }

        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        foreach ($files as $f) {
            $mime = strtolower($f->getMimeType() ?? $f->getClientMimeType() ?? '');
            if (empty($mime)) {
                $mime = strtolower(@mime_content_type($f->getPathname()) ?: '');
            }
            if (!in_array($mime, $allowedMimes, true)) {
                $ext = strtolower($f->getClientOriginalExtension());
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    return back()->withInput()->with('sales_error', 'Tipe file foto tidak diizinkan. Gunakan JPG, JPEG, PNG, atau WEBP.');
                }
            }
            if (@getimagesize($f->getPathname()) === false) {
                return back()->withInput()->with('sales_error', 'File foto harus berupa gambar yang valid.');
            }
            if ($f->getSize() !== null && $f->getSize() > ImageCompressor::MAX_SIZE_BYTES) {
                return back()->withInput()->with('sales_error', ImageCompressor::maxSizeMessage());
            }
        }

        $storedPaths = [];
        DB::beginTransaction();
        try {
            // Hanya teks diperbarui; ID, user_id, tanggal_laporan, jenis_laporan
            // dipertahankan. Relasi laporan_sales (laporan_id) tidak disentuh.
            $laporan->teks_laporan = trim($validated['teks_laporan']);
            $laporan->save();

            $nextSort = ($laporan->fotos->max('sort_order') ?? -1) + 1;
            foreach ($files as $file) {
                try {
                    $fotoPath = ImageCompressor::compressAndStore($file, 'laporan/foto');
                } catch (\RuntimeException $e) {
                    throw new \RuntimeException($e->getMessage());
                }
                $storedPaths[] = $fotoPath;
                $mimeSimpan = str_ends_with(strtolower($fotoPath), '.webp') ? 'image/webp' : 'image/jpeg';
                LaporanFoto::create([
                    'laporan_id'         => $laporan->id,
                    'foto'               => $fotoPath,
                    'foto_original_name' => $file->getClientOriginalName(),
                    'foto_mime_type'     => $mimeSimpan,
                    'sort_order'         => $nextSort++,
                ]);
                if (empty($laporan->foto)) {
                    $laporan->foto = $fotoPath;
                    $laporan->foto_original_name = $file->getClientOriginalName();
                    $laporan->foto_mime_type = $mimeSimpan;
                    $laporan->save();
                }
            }

            DB::commit();
        } catch (\RuntimeException $e) {
            DB::rollBack();
            foreach ($storedPaths as $p) {
                Storage::disk('public')->delete($p);
            }
            $msg = $e->getMessage();
            if (str_contains($msg, '10 MB') || str_contains($msg, 'Format foto') || str_contains($msg, 'gambar yang valid')) {
                return back()->withInput()->with('sales_error', $msg);
            }
            Log::error('Gagal mengedit laporan Sales', ['laporan_id' => $laporan->id, 'msg' => $msg]);
            return back()->withInput()->with('sales_error', $msg ?: 'Gagal menyimpan perubahan. Silakan coba lagi.');
        } catch (\Throwable $e) {
            DB::rollBack();
            foreach ($storedPaths as $p) {
                Storage::disk('public')->delete($p);
            }
            Log::error('Gagal mengedit laporan Sales', ['laporan_id' => $laporan->id, 'msg' => $e->getMessage()]);
            return back()->withInput()->with('sales_error', 'Gagal menyimpan perubahan. Silakan coba lagi.');
        }

        return redirect()->route('laporan.show', $laporan->id)->with('laporan_success', 'Laporan Sales berhasil diperbarui.');
    }
}
