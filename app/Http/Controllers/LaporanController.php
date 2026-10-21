<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Models\User;
use App\Support\ImageCompressor;
use App\Support\LokasiKantor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LaporanController extends Controller
{
    /**
     * Resolve user id dari authenticated Laravel user saja (Tahap 4.2).
     */
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
     * Halaman Laporan — Form + Riwayat
     * Siswa: hanya laporan miliknya
     * Admin: semua laporan (dengan filter & search optional)
     */
    public function index(Request $request)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return redirect()->route('login');
        }

        $role = $this->resolveRole($request);
        $isAdmin = $role === 'admin';

        // Filter / search (dipakai admin, siswa juga boleh search miliknya)
        $cari = trim((string) $request->query('cari', ''));
        $tanggalDari = $request->query('tanggal_dari');
        $tanggalSampai = $request->query('tanggal_sampai');

        $query = Laporan::query()->with(['user','fotos'])->orderByDesc('tanggal_laporan')->orderByDesc('id');

        // Hanya tampilkan laporan Teknisi (jenis_laporan = teknisi atau NULL untuk data lama yang belum terklasifikasi)
        // Setelah migration, Sales parent = sales, Teknisi = teknisi, ambiguous NULL dianggap teknisi untuk backward compat
        $query->teknisi();

        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('teks_laporan', 'LIKE', '%' . $cari . '%')
                  ->orWhereHas('user', function ($uq) use ($cari) {
                      $uq->where('nama', 'LIKE', '%' . $cari . '%')
                         ->orWhere('username', 'LIKE', '%' . $cari . '%')
                         ->orWhere('user_id', 'LIKE', '%' . $cari . '%');
                  });
            });
        }

        if ($tanggalDari) {
            $query->whereDate('tanggal_laporan', '>=', $tanggalDari);
        }
        if ($tanggalSampai) {
            $query->whereDate('tanggal_laporan', '<=', $tanggalSampai);
        }

        $laporans = $query->paginate(10)->withQueryString();

        $userSession = $this->userSession($request);

        // Laporan utama milik siswa pada hari ini (SEMUA jenis: teknisi/sales) —
        // dipakai view untuk menyembunyikan form Buat dan menampilkan tautan Edit.
        // Zona waktu aplikasi sudah Asia/Jakarta (config/app.php), now() = tanggal lokal.
        $laporanHariIni = null;
        if (!$isAdmin) {
            $laporanHariIni = Laporan::query()
                ->where('user_id', $user->id)
                ->whereDate('tanggal_laporan', $now = now()->toDateString())
                ->orderBy('id')
                ->first();
        }

        // Identitas siswa untuk form header (otomatis)
        // Jika kolom nis/nisn/kelas/jurusan tersedia di users akan ditampilkan, jika tidak fallback.
        $identitas = [
            'nama'     => $user->nama ?? $user->username,
            'username' => $user->username,
            'nis'      => $user->nis ?? $user->nisn ?? $user->NIS ?? $user->NISN ?? null,
            'kelas'    => $user->kelas ?? $user->kelas_jurusan ?? null,
            'jurusan'  => $user->jurusan ?? null,
            'email'    => $user->email ?? null,
            'tanggal_hari_ini' => now()->translatedFormat('d F Y'),
        ];

        return view('laporan.index', compact('userSession', 'user', 'laporans', 'identitas', 'cari', 'isAdmin', 'laporanHariIni'));
    }

    /**
     * Simpan laporan baru
     */
    public function store(Request $request)
    {
        // Admin tidak boleh membuat laporan (hanya melihat)
        if ($this->resolveRole($request) === 'admin') {
            return redirect()->route('laporan.index')->with('laporan_error', 'Admin tidak dapat membuat laporan. Fitur ini hanya untuk Siswa.');
        }
        $user = $this->resolveUser($request);
        if (!$user) {
            return redirect()->route('login')->with('laporan_error', 'Sesi tidak valid. Silakan login kembali.');
        }

        // Validasi jam laporan Teknisi (08:00-23:59) — sama dengan Sales
        $now = now();
        if (!\App\Support\WaktuAbsen::dibuka($now, 'laporan')) {
            $jam = \App\Support\WaktuAbsen::all('laporan');
            $status = \App\Support\WaktuAbsen::status($now, 'laporan');
            if ($status === 'belum') {
                return back()->withInput()->with('laporan_error', 'Laporan Teknisi belum dibuka. Jam laporan: ' . $jam['mulai'] . ' - ' . $jam['selesai'] . ' WIB.');
            } else {
                return back()->withInput()->with('laporan_error', 'Jam laporan telah berakhir. Jam laporan: ' . $jam['mulai'] . ' - ' . $jam['selesai'] . ' WIB.');
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
            return back()->withInput()->with('laporan_error', 'Maksimal 6 foto dapat diunggah untuk satu laporan.');
        }

        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        foreach ($files as $f) {
            $mime = strtolower($f->getMimeType() ?? $f->getClientMimeType() ?? '');
            if (empty($mime)) {
                $mime = strtolower(@mime_content_type($f->getPathname()) ?: '');
            }
            if (!in_array($mime, $allowedMimes, true)) {
                // Fallback cek ekstensi juga (hindari spoofing murni)
                $ext = strtolower($f->getClientOriginalExtension());
                if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
                    return back()->withInput()->with('laporan_error', 'Tipe file foto tidak diizinkan. Gunakan JPG, JPEG, PNG, atau WEBP.');
                }
            }
            // Validasi tambahan: file harus benar-benar image
            if (@getimagesize($f->getPathname()) === false) {
                return back()->withInput()->with('laporan_error', 'File foto harus berupa gambar yang valid.');
            }
            if ($f->getSize() !== null && $f->getSize() > ImageCompressor::MAX_SIZE_BYTES) {
                return back()->withInput()->with('laporan_error', ImageCompressor::maxSizeMessage());
            }
        }

        // Validasi kehadiran hari ini: harus sudah Absen Masuk (Masuk/Telat) — submit, bukan hanya buka halaman
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
            return back()->withInput()->with('laporan_error', 'Anda harus melakukan Absen Masuk terlebih dahulu sebelum membuat laporan. Silakan lakukan Absen Masuk hari ini.');
        }

        // Pembatasan 1 laporan utama per siswa per hari kalender (SEMUA jenis:
        // teknisi maupun sales — satu baris laporans per user per tanggal).
        // Cek awal di sini agar pesan jelas; cek ulang dengan lock di dalam
        // transaksi untuk menangani dua request yang masuk hampir bersamaan.
        $tanggal = $today;
        $existingHariIni = Laporan::query()
            ->where('user_id', $user->id)
            ->whereDate('tanggal_laporan', $tanggal)
            ->orderBy('id')
            ->first();
        if ($existingHariIni) {
            return redirect()->route('laporan.show', $existingHariIni->id)
                ->with('laporan_error', 'Laporan hari ini sudah dibuat. Silakan edit laporan yang tersedia.');
        }

        $storedPaths = [];
        DB::beginTransaction();
        try {
            // Cek ulang dengan pessimistic lock untuk race condition dua request
            // bersamaan. Tanpa unique constraint DB (data historis duplikat),
            // perlindungan tidak 100% mutlak — lihat dokumentasi.
            $duplikatTerkunci = Laporan::query()
                ->where('user_id', $user->id)
                ->whereDate('tanggal_laporan', $tanggal)
                ->lockForUpdate()
                ->first();
            if ($duplikatTerkunci) {
                DB::rollBack();
                return redirect()->route('laporan.show', $duplikatTerkunci->id)
                    ->with('laporan_error', 'Laporan hari ini sudah dibuat. Silakan edit laporan yang tersedia.');
            }

            $laporan = Laporan::create([
                'user_id'         => $user->id,
                'tanggal_laporan' => $tanggal,
                'teks_laporan'    => trim($validated['teks_laporan']),
                'jenis_laporan'   => Laporan::JENIS_TEKNISI,
            ]);

            foreach ($files as $idx => $file) {
                try {
                    $fotoPath = ImageCompressor::compressAndStore($file, 'laporan/foto');
                } catch (\RuntimeException $e) {
                    // Validation khusus kompres (10MB, mime, dll) -> bubble sebagai error user-friendly
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
        } catch (\RuntimeException $e) {
            DB::rollBack();
            foreach ($storedPaths as $p) {
                Storage::disk('public')->delete($p);
            }
            // Pesan validasi yang jelas (10MB, format, dll)
            $msg = $e->getMessage();
            // Jika pesan mengandung "10 MB" atau format, tampilkan langsung, jangan log sebagai error
            if (str_contains($msg, '10 MB') || str_contains($msg, 'Format foto') || str_contains($msg, 'gambar yang valid')) {
                return back()->withInput()->with('laporan_error', $msg);
            }
            Log::error('Gagal menyimpan laporan', ['user_id' => $user->id, 'msg' => $msg]);
            return back()->withInput()->with('laporan_error', $msg ?: 'Gagal menyimpan laporan. Silakan coba lagi.');
        } catch (\Throwable $e) {
            DB::rollBack();
            foreach ($storedPaths as $p) {
                Storage::disk('public')->delete($p);
            }
            Log::error('Gagal menyimpan laporan', ['user_id' => $user->id, 'msg' => $e->getMessage()]);
            return back()->withInput()->with('laporan_error', 'Gagal menyimpan laporan. Silakan coba lagi.');
        }

        return redirect()->route('laporan.index')->with('laporan_success', 'Laporan berhasil disimpan.');
    }

    /**
     * Form edit laporan Teknisi — menampilkan data laporan sebelumnya.
     * Hanya pemilik atau admin. Laporan Sales diarahkan ke form edit Sales.
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

        // Route edit Teknisi hanya untuk laporan teknisi (NULL = data lama teknisi).
        if ($laporan->jenis_laporan !== null && $laporan->jenis_laporan !== Laporan::JENIS_TEKNISI) {
            return redirect()->route('laporan.sales.edit', $laporan->id)
                ->with('sales_error', 'Laporan ini adalah laporan Sales. Silakan edit melalui halaman Sales.');
        }

        $laporan->load(['user', 'fotos']);
        $userSession = $this->userSession($request);

        return view('laporan.edit', compact('userSession', 'user', 'laporan', 'isAdmin'));
    }

    /**
     * Perbarui laporan Teknisi — mengedit record yang sama (ID, pemilik,
     * dan tanggal tidak berubah). Foto baru bersifat opsional dan ditambahkan
     * ke foto yang sudah ada (total maksimal 6); foto lama tidak dihapus.
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

        if ($laporan->jenis_laporan !== null && $laporan->jenis_laporan !== Laporan::JENIS_TEKNISI) {
            abort(403, 'Laporan ini bukan laporan Teknisi.');
        }

        // Validasi field sama dengan form pembuatan; foto opsional saat edit.
        // Field sensitif (user_id, tanggal_laporan, jenis_laporan) TIDAK
        // divalidasi/diperbarui — manipulasi request diabaikan secara aman.
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
            return back()->withInput()->with('laporan_error', 'Maksimal 6 foto per laporan. Laporan ini sudah memiliki ' . $jumlahFotoLama . ' foto, Anda mencoba menambah ' . count($files) . ' lagi.');
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
                    return back()->withInput()->with('laporan_error', 'Tipe file foto tidak diizinkan. Gunakan JPG, JPEG, PNG, atau WEBP.');
                }
            }
            if (@getimagesize($f->getPathname()) === false) {
                return back()->withInput()->with('laporan_error', 'File foto harus berupa gambar yang valid.');
            }
            if ($f->getSize() !== null && $f->getSize() > ImageCompressor::MAX_SIZE_BYTES) {
                return back()->withInput()->with('laporan_error', ImageCompressor::maxSizeMessage());
            }
        }

        $storedPaths = [];
        DB::beginTransaction();
        try {
            // Hanya teks yang diperbarui — ID, user_id, tanggal_laporan,
            // dan jenis_laporan dipertahankan apa adanya.
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
                return back()->withInput()->with('laporan_error', $msg);
            }
            Log::error('Gagal mengedit laporan', ['laporan_id' => $laporan->id, 'msg' => $msg]);
            return back()->withInput()->with('laporan_error', $msg ?: 'Gagal menyimpan perubahan. Silakan coba lagi.');
        } catch (\Throwable $e) {
            DB::rollBack();
            foreach ($storedPaths as $p) {
                Storage::disk('public')->delete($p);
            }
            Log::error('Gagal mengedit laporan', ['laporan_id' => $laporan->id, 'msg' => $e->getMessage()]);
            return back()->withInput()->with('laporan_error', 'Gagal menyimpan perubahan. Silakan coba lagi.');
        }

        return redirect()->route('laporan.show', $laporan->id)->with('laporan_success', 'Laporan berhasil diperbarui.');
    }

    /**
     * Detail laporan
     */
    public function show(Request $request, Laporan $laporan)
    {
        $user = $this->resolveUser($request);
        if (!$user) abort(403);

        $role = $this->resolveRole($request);
        $isAdmin = $role === 'admin';

        // Authorization: siswa hanya boleh lihat miliknya
        if (!$isAdmin && (int) $laporan->user_id !== (int) $user->id) {
            abort(403, 'Anda tidak berhak melihat laporan ini.');
        }

        $laporan->load(['user','fotos']);
        $userSession = $this->userSession($request);
        $lokasi = LokasiKantor::all();

        return view('laporan.show', compact('userSession', 'laporan', 'isAdmin', 'lokasi'));
    }

    /**
     * Lihat foto laporan via controller (tanpa storage:link)
     * Backward compat: jika laporan memiliki fotos relasi, tampilkan foto pertama.
     */
    public function lihatFoto(Request $request, Laporan $laporan)
    {
        $user = $this->resolveUser($request);
        if (!$user) abort(403);

        $role = $this->resolveRole($request);
        $isAdmin = $role === 'admin';
        if (!$isAdmin && (int) $laporan->user_id !== (int) $user->id) {
            abort(403, 'Anda tidak berhak melihat foto ini.');
        }

        $laporan->loadMissing('fotos');
        if ($laporan->fotos->isNotEmpty()) {
            $first = $laporan->fotos->first();
            return $this->serveFotoFile($first->foto, $first->foto_mime_type);
        }

        if (empty($laporan->foto)) {
            abort(404, 'Foto tidak tersedia.');
        }

        return $this->serveFotoFile($laporan->foto, $laporan->foto_mime_type);
    }

    public function lihatFotoById(Request $request, Laporan $laporan, LaporanFoto $foto)
    {
        $user = $this->resolveUser($request);
        if (!$user) abort(403);

        $role = $this->resolveRole($request);
        $isAdmin = $role === 'admin';
        if (!$isAdmin && (int) $laporan->user_id !== (int) $user->id) {
            abort(403, 'Anda tidak berhak melihat foto ini.');
        }

        if ((int) $foto->laporan_id !== (int) $laporan->id) {
            abort(404, 'Foto tidak ditemukan untuk laporan ini.');
        }

        return $this->serveFotoFile($foto->foto, $foto->foto_mime_type);
    }

    protected function serveFotoFile(?string $fotoPath, ?string $mimeFallback = null)
    {
        if (empty($fotoPath)) {
            abort(404, 'Foto tidak tersedia.');
        }

        $normalized = ltrim($fotoPath, '/');
        $normalized = preg_replace('#^storage/#', '', $normalized);
        $normalized = preg_replace('#^public/storage/#', '', $normalized);

        if (!Storage::disk('public')->exists($normalized)) {
            $basename = basename($normalized);
            $fallback = 'laporan/foto/' . $basename;
            if ($basename && Storage::disk('public')->exists($fallback)) {
                $normalized = $fallback;
            } else {
                abort(404, 'Foto tidak ditemukan.');
            }
        }

        $fullPath = Storage::disk('public')->path($normalized);
        $mime = null;
        try { $mime = Storage::disk('public')->mimeType($normalized); } catch (\Throwable $e) {}
        if (!$mime && file_exists($fullPath)) $mime = @mime_content_type($fullPath);
        if (!$mime) $mime = $mimeFallback ?: 'image/jpeg';
        $mime = strtolower(trim((string) $mime));

        if (!file_exists($fullPath)) abort(404);

        $originalName = basename($normalized);
        $originalName = str_replace(['"', "'", "\n", "\r"], '', $originalName);

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $originalName . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Print view (A4 portrait — layout dokumen resmi)
     * Urutan: header/logo, judul, identitas, DESKRIPSI KEGIATAN (teks), DOKUMENTASI KEGIATAN (foto 1 per baris, max 2 per halaman)
     * Foto di-inline sebagai base64 untuk kompatibilitas browser print & Dompdf (tanpa storage:link & tanpa remote).
     */
    public function print(Request $request, Laporan $laporan)
    {
        $user = $this->resolveUser($request);
        if (!$user) abort(403);
        $role = $this->resolveRole($request);
        $isAdmin = $role === 'admin';
        if (!$isAdmin && (int) $laporan->user_id !== (int) $user->id) {
            abort(403);
        }
        $laporan->load(['user','fotos']);
        $lokasi = LokasiKantor::all();

        // siapkan foto base64 untuk print inline (maksimal 6) — prioritaskan file terbaik yang ada
        $fotoBase64s = [];
        if ($laporan->fotos->isNotEmpty()) {
            foreach ($laporan->fotos as $f) {
                $normalized = ltrim($f->foto, '/');
                $normalized = preg_replace('#^storage/#', '', $normalized);
                $normalized = preg_replace('#^public/storage/#', '', $normalized);
                $full = storage_path('app/public/' . $normalized);
                if (file_exists($full)) {
                    $mime = @mime_content_type($full) ?: ($f->foto_mime_type ?: 'image/jpeg');
                    $fotoBase64s[] = [
                        'base64' => 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($full)),
                        'original_name' => $f->foto_original_name,
                    ];
                }
            }
        }
        // Fallback foto lama single (backward compat)
        if (empty($fotoBase64s) && !empty($laporan->foto)) {
            $normalized = ltrim($laporan->foto, '/');
            $normalized = preg_replace('#^storage/#', '', $normalized);
            $normalized = preg_replace('#^public/storage/#', '', $normalized);
            $full = storage_path('app/public/' . $normalized);
            if (file_exists($full)) {
                $mime = @mime_content_type($full) ?: ($laporan->foto_mime_type ?: 'image/jpeg');
                $fotoBase64s[] = [
                    'base64' => 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($full)),
                    'original_name' => $laporan->foto_original_name,
                ];
            }
        }
        // Keep single for backward compat view
        $fotoBase64 = $fotoBase64s[0]['base64'] ?? null;

        // Logo base64 untuk header (kompatibel Dompdf & browser)
        $logoBase64 = null;
        $logoPathCandidates = [public_path('images/logo-sandya.png'), public_path('images/logo-sandya2.png'), public_path('images/logo-sandya.png')];
        foreach ($logoPathCandidates as $lp) {
            if ($lp && file_exists($lp)) {
                $lmime = @mime_content_type($lp) ?: 'image/png';
                $logoBase64 = 'data:' . $lmime . ';base64,' . base64_encode(file_get_contents($lp));
                break;
            }
        }

        return view('laporan.print', compact('laporan', 'lokasi', 'fotoBase64', 'fotoBase64s', 'logoBase64'));
    }

    /**
     * Hapus laporan (opsional: pemilik atau admin)
     */
    public function destroy(Request $request, Laporan $laporan)
    {
        $user = $this->resolveUser($request);
        if (!$user) abort(403);
        $role = $this->resolveRole($request);
        $isAdmin = $role === 'admin';
        if (!$isAdmin && (int) $laporan->user_id !== (int) $user->id) {
            abort(403);
        }

        $laporan->loadMissing(['fotos','sales']);
        try {
            foreach ($laporan->fotos as $f) {
                if (!empty($f->foto)) {
                    $normalized = ltrim($f->foto, '/');
                    $normalized = preg_replace('#^storage/#', '', $normalized);
                    $normalized = preg_replace('#^public/storage/#', '', $normalized);
                    if (Storage::disk('public')->exists($normalized)) {
                        Storage::disk('public')->delete($normalized);
                    }
                }
            }
            // Fallback foto lama jika ada dan belum termigrasi
            if (!empty($laporan->foto)) {
                $existsInFotos = $laporan->fotos->contains(fn($x) => $x->foto === $laporan->foto);
                if (!$existsInFotos) {
                    $normalized = ltrim($laporan->foto, '/');
                    $normalized = preg_replace('#^storage/#', '', $normalized);
                    $normalized = preg_replace('#^public/storage/#', '', $normalized);
                    if (Storage::disk('public')->exists($normalized)) {
                        Storage::disk('public')->delete($normalized);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal hapus foto laporan saat destroy', ['id' => $laporan->id, 'msg' => $e->getMessage()]);
        }

        // Hapus data sales terkait agar tidak yatim (laporan_id nullOnDelete akan orphan)
        try {
            if (method_exists($laporan, 'sales')) {
                $laporan->sales()->delete();
            } else {
                \App\Models\LaporanSales::where('laporan_id', $laporan->id)->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal hapus sales terkait laporan', ['id' => $laporan->id, 'msg' => $e->getMessage()]);
        }

        $laporan->delete();

        return redirect()->route('laporan.index')->with('laporan_success', 'Laporan berhasil dihapus.');
    }
}
