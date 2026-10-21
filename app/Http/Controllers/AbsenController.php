<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAbsenMasukRequest;
use App\Http\Requests\StoreAbsenPulangRequest;
use App\Models\Attendance;
use App\Models\User;
use App\Support\AdminNotificationService;
use App\Support\Geo;
use App\Support\ImageCompressor;
use App\Support\LokasiKantor;
use App\Support\WaktuAbsen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AbsenController extends Controller
{
    /**
     * Data sesi pengguna aktif - sumber tunggal: authenticated Laravel user.
     * Tidak ada fallback ke session user_id/role/username.
     */
    protected function userSession(Request $request): array
    {
        $user = $request->user() ?? \Illuminate\Support\Facades\Auth::user();
        return [
            'username' => $user?->username ?? $user?->nama ?? null,
            'role'     => $user?->role ?? 'siswa',
            'user_id'  => $user?->id,
        ];
    }

    /**
     * Resolve role dari authenticated Laravel user saja.
     * Tidak ada fallback ke session user_id.
     */
    protected function resolveRole(Request $request): string
    {
        $user = $request->user() ?? \Illuminate\Support\Facades\Auth::user();
        return $user?->role ?? 'siswa';
    }

    /**
     * Cek apakah user yang sedang login adalah siswa. Admin tidak boleh absen.
     */
    protected function ensureSiswa(Request $request): ?\Illuminate\Http\RedirectResponse
    {
        $role = $this->resolveRole($request);
        if ($role !== 'siswa') {
            // Temukan redirect yang aman (jika admin mencoba POST manual)
            $request->session()->flash('absen_error', 'Hanya siswa yang dapat melakukan absensi.');
            // Jika request AJAX/JSON, abort 403 lebih tepat, tapi untuk kompatibilitas form redirect ke dashboard
            if ($request->expectsJson()) {
                abort(403, 'Hanya siswa yang dapat melakukan absensi.');
            }
            return redirect()->route('dashboard')->with('absen_error', 'Hanya siswa yang dapat melakukan absensi. Admin tidak dapat melakukan absensi.');
        }
        return null;
    }

    /**
     * Catatan absensi hari ini milik user (user_id atau nama,
     * agar catatan lama yang belum memiliki user_id tetap terdeteksi).
     */
    protected function absensiHariIni(User $user): ?Attendance
    {
        return Attendance::query()
            ->padaTanggal(now()->toDateString())
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere(function ($q2) use ($user) {
                        $q2->whereNull('user_id')
                            ->where('nama', $user->nama);
                    });
            })
            ->latest('id')
            ->first();
    }

    /**
     * Halaman Absen Masuk.
     * Menampilkan identitas user dari database, status absensi hari ini,
     * dan konfigurasi lokasi untuk validasi GPS.
     */
    public function index(Request $request)
    {
        $user = $request->user() ?? \Illuminate\Support\Facades\Auth::user();

        if (! $user) {
            $request->session()->flash('absen_error', 'Sesi pengguna tidak valid. Silakan login kembali.');

            return redirect()->route('login');
        }

        // Authorization langsung dari authenticated user (Tahap 4.2 FINAL CLEANUP)
        $isAdmin = $user->role === 'admin';
        $userSession = $this->userSession($request);

        // Jika admin: tampilkan tabel data siswa yang masuk hari ini (bukan form)
        if ($isAdmin) {
            $lokasi = LokasiKantor::all();
            $waktuAbsen = [
                'mulai'   => WaktuAbsen::mulai(),
                'selesai' => WaktuAbsen::selesai(),
                'status'  => WaktuAbsen::status(),
            ];
            $tanggalHariIni = now()->toDateString();
            $items = Attendance::query()
                ->with('user')
                ->whereNotNull('tgl_masuk')
                ->whereNotNull('jam_masuk')
                ->padaTanggal($tanggalHariIni)
                ->orderBy('jam_masuk', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            // Enrich untuk tampilan tabel
            $items->transform(function ($row) {
                $row->nama_tampil = $row->user?->nama ?? $row->nama;
                $row->status_label = $row->status_kehadiran ?: ($row->status ?: 'Masuk');
                return $row;
            });

            $details = $items->mapWithKeys(function ($row) use ($lokasi) {
                $fotoUrl = $row->foto ? route('foto.lihat', $row->id) : null;
                return [$row->id => [
                    'nama' => $row->nama_tampil,
                    'tanggal' => $row->tgl_masuk ? \Carbon\Carbon::parse($row->tgl_masuk)->format('d-m-Y') : '-',
                    'tanggal_long' => $row->tgl_masuk ? \Carbon\Carbon::parse($row->tgl_masuk)->translatedFormat('d F Y') : '-',
                    'hari' => $row->tgl_masuk ? \Carbon\Carbon::parse($row->tgl_masuk)->translatedFormat('l') : '-',
                    'jam_masuk' => $row->jam_masuk ?? '-',
                    'status_kehadiran' => $row->status_label,
                    'keterangan' => $row->keterangan,
                    'foto' => $fotoUrl,
                    'latitude' => $row->latitude,
                    'longitude' => $row->longitude,
                    'accuracy' => $row->accuracy,
                    'jarak' => $row->jarak,
                    'radius' => $row->radius_kantor_saat_absen,
                    'bukti_file' => $row->bukti_file ? route('bukti.lihat', $row->id) : null,
                    'bukti_original_name' => $row->bukti_original_name,
                ]];
            });

            $absensiHariIni = null;
            $isAdmin = true;
            return view('absen.masuk', compact('userSession', 'user', 'isAdmin', 'items', 'details', 'lokasi', 'waktuAbsen', 'tanggalHariIni', 'absensiHariIni'));
        }

        // Siswa: alur normal
        // Cek apakah user sudah absen masuk hari ini.
        $absensiHariIni = $this->absensiHariIni($user);

        // Lokasi Kantor (sumber validasi GPS, dapat diubah admin).
        $lokasi = LokasiKantor::all();

        // Jam buka Absen Masuk (08:00 - 08:30 WIB, dapat diubah admin).
        $waktuAbsen = [
            'mulai'   => WaktuAbsen::mulai(),
            'selesai' => WaktuAbsen::selesai(),
            'status'  => WaktuAbsen::status(),
        ];

        $isAdmin = false;
        return view('absen.masuk', compact('userSession', 'user', 'absensiHariIni', 'lokasi', 'waktuAbsen', 'isAdmin'));
    }

    /**
     * Proses Absen Masuk.
     * Waktu resmi diambil dari server, identitas dari database,
     * dan validasi radius dilakukan ulang di backend.
     */
    public function store(StoreAbsenMasukRequest $request)
    {
        $user = $request->user() ?? \Illuminate\Support\Facades\Auth::user();

        if (! $user) {
            $request->session()->flash('absen_error', 'Sesi pengguna tidak valid. Silakan login kembali.');

            return redirect()->route('login');
        }

        // Hanya siswa yang boleh absen
        if ($check = $this->ensureSiswa($request)) {
            return $check;
        }

        $now = now();

        // ---- Jenis kehadiran (Masuk | Izin | Dispensasi | Sakit) ----
        // Ditentukan lebih awal agar validasi waktu hanya berlaku untuk Masuk.
        // Telat ditentukan otomatis oleh backend berdasarkan waktu server (Asia/Jakarta).
        $statusKehadiran = $request->input('status_kehadiran', 'Masuk');
        if (! in_array($statusKehadiran, Attendance::STATUS_KEHADIRAN_INPUT_LIST, true)) {
            // Jika user mengirim Telat/Tidak Masuk manual, anggap Masuk dan biarkan backend tentukan Telat
            if ($statusKehadiran === Attendance::STATUS_KEHADIRAN_TELAT) {
                $statusKehadiran = Attendance::STATUS_KEHADIRAN_MASUK;
            } else {
                $statusKehadiran = Attendance::STATUS_KEHADIRAN_MASUK;
            }
        }
        $isMasukInput = $statusKehadiran === Attendance::STATUS_KEHADIRAN_MASUK;

        // ---- Cek jendela waktu Absen Masuk HANYA untuk status Masuk ----
        // Waktu resmi dari server Laravel (Asia/Jakarta), bukan dari HP user.
        // Izin/Dispensasi/Sakit bebas waktu: dapat dikirim kapan saja.
        // Masuk: sebelum 08:00 -> tolak (belum dibuka), 08:00-08:30 -> Masuk, >08:30 -> Telat (tetap boleh absen)
        if ($isMasukInput) {
            $statusWaktu = WaktuAbsen::status($now, 'masuk');
            if ($statusWaktu === 'belum') {
                $pesan = 'Absen Masuk belum dibuka. Silakan melakukan absensi mulai pukul '.WaktuAbsen::mulai().' WIB.';
                return redirect()->route('absen.masuk')->with('absen_error', $pesan);
            }
            // Jika sudah lewat batas (tutup) => Telat, bukan ditolak
            if ($statusWaktu === 'tutup') {
                $statusKehadiran = Attendance::STATUS_KEHADIRAN_TELAT;
            } else {
                $statusKehadiran = Attendance::STATUS_KEHADIRAN_MASUK;
            }
        }
        $isMasuk = in_array($statusKehadiran, [Attendance::STATUS_KEHADIRAN_MASUK, Attendance::STATUS_KEHADIRAN_TELAT], true);

        // ---- Cegah absen ganda (backend, bukan hanya JS) ----
        $sudahAbsen = Attendance::query()
            ->padaTanggal($now->toDateString())
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere(function ($q2) use ($user) {
                        $q2->whereNull('user_id')
                            ->where('nama', $user->nama);
                    });
            })
            ->exists();

        if ($sudahAbsen) {
            return redirect()->route('absen.masuk')
                ->with('absen_error', 'Anda sudah melakukan absen masuk hari ini.');
        }
        $keterangan = $request->input('keterangan');
        // Batasi panjang keterangan untuk keamanan.
        if ($keterangan !== null) {
            $keterangan = trim((string) $keterangan);
            if ($keterangan === '') {
                $keterangan = null;
            } elseif (mb_strlen($keterangan) > 500) {
                $keterangan = mb_substr($keterangan, 0, 500);
            }
        }

        // Tetap gunakan $isMasuk yang sudah mencakup Telat (di atas)

        // ---- Validasi keterangan wajib untuk Izin/Dispensasi/Sakit ----
        if (! $isMasuk) {
            if ($keterangan === null || trim((string) $keterangan) === '') {
                return redirect()->route('absen.masuk')
                    ->with('absen_error', 'Keterangan wajib diisi untuk status ' . $statusKehadiran . '.')
                    ->withInput();
            }
        }

        // ---- Validasi bukti wajib untuk Dispensasi/Sakit (backend, tidak hanya JS) ----
        // Izin: bukti opsional, Dispensasi/Sakit: WAJIB, Masuk/Telat: diabaikan
        if ($statusKehadiran === Attendance::STATUS_KEHADIRAN_DISPENSASI && ! $request->hasFile('bukti_file')) {
            return redirect()->route('absen.masuk')
                ->with('absen_error', 'Bukti dispensasi wajib diupload.')
                ->withInput();
        }
        if ($statusKehadiran === Attendance::STATUS_KEHADIRAN_SAKIT && ! $request->hasFile('bukti_file')) {
            return redirect()->route('absen.masuk')
                ->with('absen_error', 'Bukti sakit wajib diupload.')
                ->withInput();
        }

        $latitude  = null;
        $longitude = null;
        $accuracy  = null;
        $jarak     = null;
        $lokasi    = LokasiKantor::all();

        if ($isMasuk) {
            $latitude  = $request->filled('latitude') ? (float) $request->latitude : null;
            $longitude = $request->filled('longitude') ? (float) $request->longitude : null;
            $accuracy  = $request->filled('accuracy') ? (float) $request->accuracy : null;

            // ---- Foto absen (selfie) wajib jika dikonfigurasi (hanya untuk Masuk) ----
            if (config('absensi.foto_wajib', true) && ! $request->hasFile('foto')) {
                return redirect()->route('absen.masuk')
                    ->with('absen_error', 'Foto absen wajib diambil sebelum melakukan absen masuk.');
            }

            // ---- Validasi radius & akurasi di server (dihitung ulang) ----
            if ($latitude !== null && $longitude !== null) {
                $maxAccuracy = (float) config('absensi.max_accuracy', 150);

                if ($accuracy !== null && $accuracy > $maxAccuracy) {
                    return redirect()->route('absen.masuk')
                        ->with('absen_error', 'Akurasi GPS terlalu rendah ('.round($accuracy).' m). Mendekatlah ke area kantor dan coba lagi.');
                }

                $jarak = Geo::hitungJarak(
                    $latitude,
                    $longitude,
                    (float) $lokasi['latitude'],
                    (float) $lokasi['longitude']
                );

                if ($jarak > (float) $lokasi['radius']) {
                    return redirect()->route('absen.masuk')
                        ->with('absen_error', 'Anda berada di luar Radius Kantor (jarak '.round($jarak).' m).');
                }
            } elseif (config('absensi.gps_wajib', true)) {
                return redirect()->route('absen.masuk')
                    ->with('absen_error', 'Lokasi belum ditemukan. Silakan aktifkan GPS dan coba lagi.');
            }
        } else {
            // Untuk Izin/Dispensasi/Sakit: tanpa GPS, tanpa radius, foto tidak wajib.
            // Pastikan tidak menyimpan koordinat palsu; paksa null agar tidak error.
            $latitude  = null;
            $longitude = null;
            $accuracy  = null;
            $jarak     = null;
        }

        // ---- Simpan foto selfie HANYA untuk Masuk/Telat (non-Masuk jangan simpan) ----
        // Sesuai spec B: untuk Izin/Dispensasi/Sakit foto selfie tidak disimpan
        $fotoPath = null;

        if ($isMasuk) {
            if ($request->hasFile('foto')) {
                try {
                    $fotoPath = ImageCompressor::compressAndStore($request->file('foto'), 'absen/foto');
                } catch (\RuntimeException $e) {
                    return redirect()->route('absen.masuk')
                        ->with('absen_error', $e->getMessage())
                        ->withInput();
                } catch (\Throwable $e) {
                    Log::error('Gagal menyimpan foto absen (unexpected)', [
                        'user_id' => $user->id,
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    return redirect()->route('absen.masuk')
                        ->with('absen_error', 'Foto gagal diproses di server. Silakan coba lagi dengan foto yang lebih kecil.')
                        ->withInput();
                }
            } elseif (config('absensi.foto_wajib', true)) {
                return redirect()->route('absen.masuk')
                    ->with('absen_error', 'Foto absen wajib diambil sebelum melakukan absen masuk. Silakan ambil foto selfie terlebih dahulu.');
            }
        } else {
            // Untuk Izin/Dispensasi/Sakit, abaikan file foto selfie jika ada (jangan simpan, jangan validasi)
            $fotoPath = null;
        }

        // ---- Simpan bukti pendukung ----
        // Izin: opsional, Dispensasi/Sakit: wajib (sudah divalidasi di atas), Masuk: diabaikan
        $buktiPath = null;
        $buktiOriginal = null;
        $buktiMime = null;

        if (! $isMasuk && $request->hasFile('bukti_file')) {
            // Validasi sudah di Request, tapi cek lagi untuk keamanan
            $file = $request->file('bukti_file');
            $allowedExts = ['jpg','jpeg','png','pdf'];
            $ext = strtolower($file->getClientOriginalExtension());
            $mime = $file->getMimeType();

            // Pastikan ekstensi sesuai MIME yang diizinkan (cegah spoofing)
            if (! in_array($ext, $allowedExts, true)) {
                return redirect()->route('absen.masuk')
                    ->with('absen_error', 'Format bukti harus JPG, JPEG, PNG, atau PDF.');
            }

            try {
                $buktiOriginal = $file->getClientOriginalName();
                $buktiMime = $mime;
                // Nama file aman & unik (hashName) di storage
                $buktiPath = $file->store('absen/bukti', 'public');
            } catch (\Throwable $e) {
                Log::error('Gagal menyimpan bukti pendukung', [
                    'user_id' => $user->id,
                    'message' => $e->getMessage(),
                ]);

                return redirect()->route('absen.masuk')
                    ->with('absen_error', 'Bukti gagal disimpan. Periksa izin penyimpanan server.');
            }
        }
        // Untuk Masuk, abaikan bukti_file meskipun ada manipulasi request

        // ---- Simpan ke tabel attendances (tanggal & jam resmi server WIB) ----
        // radius_kantor_saat_absen: snapshot Radius Kantor yang berlaku pada
        // saat absensi, agar histori lama tidak berubah mengikuti pengaturan
        // Radius Kantor terbaru. Untuk status tanpa GPS, snapshot diisi null.
        $savedAttendance = null;
        try {
            $savedAttendance = Attendance::create([
                'user_id'                  => $user->id,
                'nama'                     => $user->nama ?: $user->username,
                'status'                   => $statusKehadiran,
                'status_kehadiran'         => $statusKehadiran,
                'keterangan'               => $keterangan,
                'bukti_file'               => $buktiPath,
                'bukti_original_name'      => $buktiOriginal,
                'bukti_mime_type'          => $buktiMime,
                'tgl_masuk'                => $now->toDateString(),
                'jam_masuk'                => $now->format('H:i:s'),
                'latitude'                 => $latitude,
                'longitude'                => $longitude,
                'accuracy'                 => $accuracy,
                'foto'                     => $fotoPath,
                'jarak'                    => $jarak ?? null,
                'radius_kantor_saat_absen' => $isMasuk ? $lokasi['radius'] : null,
            ]);
        } catch (\Throwable $e) {
            // Jika penyimpanan database gagal, hapus file yang barusan diunggah.
            if ($fotoPath) {
                Storage::disk('public')->delete($fotoPath);
            }
            if ($buktiPath) {
                Storage::disk('public')->delete($buktiPath);
            }

            Log::error('Gagal menyimpan absen masuk', [
                'user_id' => $user->id,
                'status_kehadiran' => $statusKehadiran,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('absen.masuk')
                ->with('absen_error', 'Gagal menyimpan absensi. Silakan coba lagi.');
        }

        Log::info('Absen masuk berhasil', [
            'user_id'  => $user->id,
            'username' => $user->username,
            'status_kehadiran' => $statusKehadiran,
            'waktu'    => $now->format('Y-m-d H:i:s'),
            'foto'     => $fotoPath,
            'bukti'    => $buktiPath,
            'jarak'    => $jarak ?? null,
        ]);

        // Notifikasi admin untuk Izin/Dispensasi/Sakit — jangan ganggu alur utama jika gagal
        if ($savedAttendance && in_array($statusKehadiran, [Attendance::STATUS_KEHADIRAN_IZIN, Attendance::STATUS_KEHADIRAN_DISPENSASI, Attendance::STATUS_KEHADIRAN_SAKIT], true)) {
            try {
                // Pastikan relasi user terisi agar nama akurat
                $savedAttendance->loadMissing('user');
                AdminNotificationService::notifyForAttendance($savedAttendance);
            } catch (\Throwable $e) {
                Log::warning('Notifikasi Izin/Dispensasi/Sakit gagal tetapi absensi tetap sukses', [
                    'attendance_id' => $savedAttendance->id ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($statusKehadiran === Attendance::STATUS_KEHADIRAN_MASUK) {
            $pesanSukses = 'Absen masuk berhasil tercatat pada pukul '.$now->format('H:i').'.';
        } elseif ($statusKehadiran === Attendance::STATUS_KEHADIRAN_TELAT) {
            $pesanSukses = 'Absen masuk (Telat) berhasil tercatat pada pukul '.$now->format('H:i').'.';
        } else {
            $pesanSukses = 'Kehadiran '. $statusKehadiran .' berhasil tercatat pada pukul '.$now->format('H:i').'.';
        }

        return redirect()->route('absen.masuk')
            ->with('absen_success', $pesanSukses);
    }

    /**
     * Halaman Absen Pulang.
     * Menampilkan status absensi hari ini, konfigurasi lokasi untuk
     * validasi GPS, dan jendela Absen Pulang FIXED 17:00 - 23:00 WIB.
     */
    public function pulang(Request $request)
    {
        $user = $request->user() ?? \Illuminate\Support\Facades\Auth::user();

        if (! $user) {
            $request->session()->flash('absen_error', 'Sesi pengguna tidak valid. Silakan login kembali.');

            return redirect()->route('login');
        }

        // Authorization langsung dari authenticated user (Tahap 4.2 FINAL CLEANUP)
        $isAdmin = $user->role === 'admin';
        $userSession = $this->userSession($request);

        // Jika admin: tampilkan tabel data siswa yang pulang hari ini
        if ($isAdmin) {
            $lokasi = LokasiKantor::all();
            $tanggalHariIni = now()->toDateString();
            // Siswa yang sudah pulang hari ini: tgl_pulang = hari ini atau tgl_masuk = hari ini + jam_pulang not null
            $items = Attendance::query()
                ->with('user')
                ->whereNotNull('jam_pulang')
                ->where(function ($q) use ($tanggalHariIni) {
                    $q->whereDate('tgl_pulang', $tanggalHariIni)
                      ->orWhere(function ($q2) use ($tanggalHariIni) {
                          $q2->whereDate('tgl_masuk', $tanggalHariIni)
                             ->whereNotNull('tgl_pulang');
                      });
                })
                ->orderBy('jam_pulang', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            // Fallback: jika tgl_pulang tidak konsisten, tetap tampilkan yang padaTanggal hari ini + jam_pulang
            if ($items->isEmpty()) {
                $items = Attendance::query()
                    ->with('user')
                    ->whereNotNull('jam_pulang')
                    ->padaTanggal($tanggalHariIni)
                    ->orderBy('jam_pulang', 'asc')
                    ->get();
            }

            $items->transform(function ($row) {
                $row->nama_tampil = $row->user?->nama ?? $row->nama;
                // status pulang: jika ada jam_pulang anggap Pulang, fallback ke status_kehadiran
                $row->status_label = 'Pulang';
                return $row;
            });

            $details = $items->mapWithKeys(function ($row) use ($lokasi) {
                $fotoUrl = $row->foto_pulang ? route('foto.lihat', $row->id) . '?tipe=pulang' : null;
                return [$row->id => [
                    'nama' => $row->nama_tampil,
                    'tanggal' => $row->tgl_pulang ? \Carbon\Carbon::parse($row->tgl_pulang)->format('d-m-Y') : ($row->tgl_masuk ? \Carbon\Carbon::parse($row->tgl_masuk)->format('d-m-Y') : '-'),
                    'tanggal_long' => $row->tgl_pulang ? \Carbon\Carbon::parse($row->tgl_pulang)->translatedFormat('d F Y') : ($row->tgl_masuk ? \Carbon\Carbon::parse($row->tgl_masuk)->translatedFormat('d F Y') : '-'),
                    'hari' => $row->tgl_pulang ? \Carbon\Carbon::parse($row->tgl_pulang)->translatedFormat('l') : ($row->tgl_masuk ? \Carbon\Carbon::parse($row->tgl_masuk)->translatedFormat('l') : '-'),
                    'jam_pulang' => $row->jam_pulang ?? '-',
                    'jam_masuk' => $row->jam_masuk ?? '-',
                    'status_kehadiran' => $row->status_label,
                    'status_masuk' => $row->status_kehadiran ?? $row->status,
                    'foto' => $fotoUrl,
                    'latitude' => $row->latitude_pulang ?? $row->latitude,
                    'longitude' => $row->longitude_pulang ?? $row->longitude,
                    'accuracy' => $row->accuracy_pulang ?? $row->accuracy,
                    'jarak' => $row->jarak_pulang ?? $row->jarak,
                    'radius' => $row->radius_kantor_saat_pulang ?? $row->radius_kantor_saat_absen,
                ]];
            });

            $waktuAbsen = [
                'mulai' => WaktuAbsen::mulai('pulang'),
                'selesai' => WaktuAbsen::selesai('pulang'),
                'status' => WaktuAbsen::status(null, 'pulang'),
                'jamMasuk' => null,
                'isExempt' => false,
                'isSelesaiTanpaPulang' => false,
                'statusKehadiran' => null,
            ];

            $isExempt = false;
            $isSelesaiTanpaPulang = false;
            $statusKehadiran = null;
            $absensiHariIni = null;
            $sudahMasuk = false;
            $sudahPulang = false;
            $bolehAbsen = false;

            return view('absen.pulang', compact('userSession', 'user', 'isAdmin', 'items', 'details', 'lokasi', 'waktuAbsen', 'tanggalHariIni', 'isExempt', 'isSelesaiTanpaPulang', 'statusKehadiran', 'absensiHariIni', 'sudahMasuk', 'sudahPulang', 'bolehAbsen'));
        }

        // Siswa: alur normal
        // Catatan absensi hari ini milik user (user_id atau nama,
        // agar catatan lama yang belum memiliki user_id tetap terbaca).
        $absensiHariIni = $this->absensiHariIni($user);

        // Lokasi Kantor (sumber validasi GPS, dapat diubah admin).
        $lokasi = LokasiKantor::all();

        // Logika utama Absen Pulang:
        // Masuk -> wajib pulang (jendela tetap 17:00-23:00 WIB)
        // Izin/Dispensasi/Sakit -> selesai otomatis, tidak perlu pulang

        $statusKehadiran = $absensiHariIni?->status_kehadiran ?? $absensiHariIni?->status;
        $isExempt = $absensiHariIni && Attendance::isExemptFromPulang($statusKehadiran);
        $isSelesaiTanpaPulang = $isExempt && $absensiHariIni?->jam_masuk !== null;

        // Jendela Absen Pulang FIXED 17:00 - 23:00 WIB (server WIB).
        // Semua siswa memiliki waktu mulai sama: 17:00 WIB.
        // Untuk status exempt, jendela tidak relevan.
        $mulaiPulang = WaktuAbsen::mulai('pulang');
        $statusPulang = 'belum';

        if ($isSelesaiTanpaPulang) {
            $statusPulang = 'selesai';
        } else {
            $statusPulang = WaktuAbsen::status(now(), 'pulang');
        }

        $waktuAbsen = [
            'mulai'              => $mulaiPulang,
            'selesai'            => WaktuAbsen::selesai('pulang'),
            'status'             => $statusPulang,
            'jamMasuk'           => $absensiHariIni?->jam_masuk,
            'isExempt'           => $isExempt,
            'isSelesaiTanpaPulang'=> $isSelesaiTanpaPulang,
            'statusKehadiran'    => $statusKehadiran,
        ];

        return view('absen.pulang', compact('userSession', 'user', 'absensiHariIni', 'lokasi', 'waktuAbsen', 'isExempt', 'isSelesaiTanpaPulang', 'statusKehadiran', 'isAdmin'));
    }

    /**
     * Proses Absen Pulang.
     *
     * Validasi berurutan (semua di server, waktu resmi WIB Asia/Jakarta):
     * 1. User login & record absensi hari ini tersedia.
     * 2. Wajib sudah Absen Masuk hari ini.
     * 3. Belum pernah Absen Pulang hari ini.
     * 4. Jendela waktu FIXED: 17:00 - 23:00 WIB (inklusif) —
     *    semua siswa sama: >=17:00 WIB.
     * 5. Foto selfie pulang wajib (bukan foto masuk).
     * 6. GPS wajib & akurasi cukup.
     * 7. Jarak ke kantor dihitung ulang dan dibandingkan dengan Radius Kantor.
     *
     * Record Attendance yang SAMA di-update (tgl_pulang, jam_pulang,
     * foto_pulang, GPS pulang, jarak pulang, radius snapshot) — tidak
     * membuat record baru dan tidak mengubah data Absen Masuk.
     */
    public function pulangStore(StoreAbsenPulangRequest $request)
    {
        $user = $request->user() ?? \Illuminate\Support\Facades\Auth::user();

        if (! $user) {
            $request->session()->flash('absen_error', 'Sesi pengguna tidak valid. Silakan login kembali.');

            return redirect()->route('login');
        }

        // Hanya siswa yang boleh absen pulang
        if ($check = $this->ensureSiswa($request)) {
            return $check;
        }

        $now = now();

        // ---- Record absensi hari ini milik user (bukan data dari frontend) ----
        $absensi = $this->absensiHariIni($user);

        // Belum absen masuk -> tidak boleh absen pulang.
        if (! $absensi || ! $absensi->jam_masuk) {
            return redirect()->route('absen.pulang')
                ->with('absen_error', 'Anda belum melakukan absen masuk hari ini.');
        }

        // Logika utama: hanya Masuk yang wajib pulang.
        // Izin/Dispensasi/Sakit dianggap selesai tanpa Absen Pulang.
        $statusKehadiranPulang = $absensi->status_kehadiran ?? $absensi->status;
        if (Attendance::isExemptFromPulang($statusKehadiranPulang)) {
            return redirect()->route('absen.pulang')
                ->with('absen_error', 'Absensi selesai — Anda tidak perlu melakukan Absen Pulang karena status Anda adalah '.$statusKehadiranPulang.'.');
        }

        // Sudah pernah absen pulang -> cegah duplikasi (backend, bukan hanya JS).
        if ($absensi->jam_pulang) {
            return redirect()->route('absen.pulang')
                ->with('absen_error', 'Anda sudah melakukan absen pulang hari ini.');
        }

        // Pastikan hanya status Masuk yang diproses untuk pulang (toleransi legacy Pulang/Belum Pulang)
        if ($statusKehadiranPulang && ! Attendance::requiresPulang($statusKehadiranPulang) && ! in_array($statusKehadiranPulang, ['Pulang', 'Belum Pulang'], true)) {
            return redirect()->route('absen.pulang')
                ->with('absen_error', 'Absensi selesai — Anda tidak perlu melakukan Absen Pulang karena status Anda adalah '.$statusKehadiranPulang.'.');
        }

        // ---- Cek jendela waktu Absen Pulang FIXED 17:00 - 23:00 WIB ----
        // Semua siswa: mulai 17:00 WIB.
        // Waktu resmi dari server Laravel (Asia/Jakarta), bukan dari HP/browser/JavaScript.
        $statusPulang = WaktuAbsen::status($now, 'pulang');

        if ($statusPulang !== 'buka') {
            if ($statusPulang === 'belum') {
                $mulaiPulang  = WaktuAbsen::mulai('pulang');
                $pesan = 'Absen Pulang belum dapat dilakukan. Anda dapat melakukan Absen Pulang mulai pukul '.$mulaiPulang.' WIB.';
            } else {
                $pesan = 'Absen Pulang sudah ditutup. Batas waktu Absen Pulang adalah pukul '.WaktuAbsen::selesai('pulang').' WIB.';
            }

            return redirect()->route('absen.pulang')
                ->with('absen_error', $pesan);
        }

        $latitude  = $request->filled('latitude') ? (float) $request->latitude : null;
        $longitude = $request->filled('longitude') ? (float) $request->longitude : null;
        $accuracy  = $request->filled('accuracy') ? (float) $request->accuracy : null;

        // Lokasi Kantor dari database (bukan dari request user).
        $lokasi = LokasiKantor::all();
        $jarakPulang = null;

        // ---- Foto Absen Pulang (selfie) wajib ----
        // Foto pulang disimpan terpisah dari foto masuk (kolom foto_pulang).
        if (config('absensi.foto_wajib', true) && ! $request->hasFile('foto')) {
            return redirect()->route('absen.pulang')
                ->with('absen_error', 'Foto absen pulang wajib diambil sebelum melakukan absen pulang.');
        }

        // ---- Validasi radius & akurasi di server (dihitung ulang) ----
        if ($latitude !== null && $longitude !== null) {
            $maxAccuracy = (float) config('absensi.max_accuracy', 150);

            if ($accuracy !== null && $accuracy > $maxAccuracy) {
                return redirect()->route('absen.pulang')
                    ->with('absen_error', 'Akurasi GPS terlalu rendah ('.round($accuracy).' m). Mendekatlah ke area kantor dan coba lagi.');
            }

            $jarakPulang = Geo::hitungJarak(
                $latitude,
                $longitude,
                (float) $lokasi['latitude'],
                (float) $lokasi['longitude']
            );

            if ($jarakPulang > (float) $lokasi['radius']) {
                return redirect()->route('absen.pulang')
                    ->with('absen_error', 'Anda berada di luar Radius Kantor (jarak '.round($jarakPulang).' m).');
            }
        } elseif (config('absensi.gps_wajib', true)) {
            return redirect()->route('absen.pulang')
                ->with('absen_error', 'Lokasi belum ditemukan. Silakan aktifkan GPS dan coba lagi.');
        }

        // ---- Simpan foto pulang ke storage publik (path saja yang masuk database) ----
        // Auto kompres: resize max 1600x1600, quality 80%, validasi 10MB, simpan hasil kompres saja
        $fotoPulangPath = null;

        if ($request->hasFile('foto')) {
            try {
                $fotoPulangPath = ImageCompressor::compressAndStore($request->file('foto'), 'absen/foto-pulang');
            } catch (\RuntimeException $e) {
                return redirect()->route('absen.pulang')
                    ->with('absen_error', $e->getMessage())
                    ->withInput();
            } catch (\Throwable $e) {
                Log::error('Gagal menyimpan foto absen pulang', [
                    'user_id' => $user->id,
                    'message' => $e->getMessage(),
                ]);

                return redirect()->route('absen.pulang')
                    ->with('absen_error', 'Foto gagal diproses. Silakan coba lagi.');
            }
        }

        // ---- Update record Attendance yang SAMA (tidak membuat record baru) ----
        // Data Absen Masuk (tgl_masuk, jam_masuk, foto, GPS masuk, jarak,
        // radius_kantor_saat_absen) TIDAK diubah. Radius Kantor yang berlaku
        // saat Absen Pulang disimpan sebagai snapshot (radius_kantor_saat_pulang).
        try {
            $absensi->update([
                'tgl_pulang'                => $now->toDateString(),
                'jam_pulang'                => $now->format('H:i:s'),
                'foto_pulang'               => $fotoPulangPath,
                'latitude_pulang'           => $latitude,
                'longitude_pulang'          => $longitude,
                'accuracy_pulang'           => $accuracy,
                'jarak_pulang'              => $jarakPulang ?? null,
                'radius_kantor_saat_pulang' => $lokasi['radius'],
                'status'                    => 'Pulang',
            ]);
        } catch (\Throwable $e) {
            // Jika penyimpanan database gagal, hapus file foto yang barusan diunggah.
            if ($fotoPulangPath) {
                Storage::disk('public')->delete($fotoPulangPath);
            }

            Log::error('Gagal menyimpan absen pulang', [
                'user_id'    => $user->id,
                'attendance' => $absensi->id,
                'message'    => $e->getMessage(),
            ]);

            return redirect()->route('absen.pulang')
                ->with('absen_error', 'Gagal menyimpan absensi. Silakan coba lagi.');
        }

        Log::info('Absen pulang berhasil', [
            'user_id'    => $user->id,
            'username'   => $user->username,
            'waktu'      => $now->format('Y-m-d H:i:s'),
            'attendance' => $absensi->id,
            'foto'       => $fotoPulangPath,
            'jarak'      => $jarakPulang ?? null,
        ]);

        return redirect()->route('absen.pulang')
            ->with('absen_success', 'Absen pulang berhasil tercatat pada pukul '.$now->format('H:i').'.');
    }

    /**
     * Menampilkan file bukti pendukung (JPG/JPEG/PNG/PDF) tanpa bergantung pada symbolic link.
     * Kompatibel dengan localhost, shared hosting, dan InfinityFree.
     *
     * Route: GET /bukti/{attendance}/lihat  -> name bukti.lihat
     * Middleware: auth (dicek di route), ditambah pengecekan role pemilik.
     */
    public function lihatBukti(Request $request, Attendance $attendance)
    {
        // ---- Autentikasi: hanya authenticated Laravel user (Tahap 4.2) ----
        $user = $request->user() ?? \Illuminate\Support\Facades\Auth::user();
        $role = $user?->role ?? $this->resolveRole($request);

        if (! $user) {
            abort(403, 'Akses ditolak. Silakan login kembali.');
        }

        // ---- Authorization: admin boleh lihat semua, siswa hanya miliknya ----
        $isAdmin = ($role === 'admin');
        $isOwner = $attendance->user_id && (int) $attendance->user_id === (int) $user->id;
        // Fallback untuk data lama tanpa user_id: cek nama
        if (! $isAdmin && ! $isOwner) {
            // Jika bukan admin dan bukan pemilik, tolak
            // Untuk data lama yang user_id null, bandingkan nama
            if ($attendance->nama !== $user->nama) {
                abort(403, 'Anda tidak berhak melihat bukti ini.');
            }
        }

        // ---- Cek bukti tersedia ----
        $buktiFile = $attendance->bukti_file;

        if (empty($buktiFile)) {
            abort(404, 'Bukti tidak ditemukan atau file sudah tidak tersedia.');
        }

        // Normalisasi path: hapus prefix yang mungkin double
        // Database seharusnya menyimpan "absen/bukti/namafile.ext" tanpa "storage/" atau "/storage/"
        $normalized = ltrim($buktiFile, '/');
        $normalized = preg_replace('#^storage/#', '', $normalized);
        $normalized = preg_replace('#^public/storage/#', '', $normalized);
        // Jika masih mengandung "storage/app/public", bersihkan
        $normalized = preg_replace('#^storage/app/public/#', '', $normalized);

        // Cek file benar-benar ada di disk public
        if (! Storage::disk('public')->exists($normalized)) {
            // Coba fallback: jika path lama disimpan dengan prefix berbeda, coba cari dengan basename
            // Misal file lama sudah dipindah, coba cek dengan basename saja di folder bukti
            $basename = basename($normalized);
            $fallback = 'absen/bukti/' . $basename;
            if ($basename && Storage::disk('public')->exists($fallback)) {
                $normalized = $fallback;
            } else {
                abort(404, 'Bukti tidak ditemukan atau file sudah tidak tersedia.');
            }
        }

        // ---- Deteksi MIME type yang benar dari file fisik ----
        $fullPath = Storage::disk('public')->path($normalized);
        $mime = null;

        try {
            $mime = Storage::disk('public')->mimeType($normalized);
        } catch (\Throwable $e) {
            // fallback
        }

        if (! $mime && file_exists($fullPath)) {
            $mime = @mime_content_type($fullPath);
        }

        // Fallback dari database jika deteksi gagal
        if (! $mime) {
            $mime = $attendance->bukti_mime_type;
        }

        // Normalisasi mime
        $mime = strtolower(trim((string) $mime));

        // Izinkan hanya tipe yang diperbolehkan (sesuai validasi upload)
        $allowedMimes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'application/pdf',
        ];

        // Juga cek ekstensi sebagai cadangan
        $ext = strtolower(pathinfo($attendance->bukti_original_name ?? $normalized, PATHINFO_EXTENSION));
        $extAllowed = ['jpg','jpeg','png','pdf'];

        $isExtensionAllowed = in_array($ext, $extAllowed, true);
        $isMimeAllowed = in_array($mime, $allowedMimes, true) || str_starts_with($mime, 'image/') && $isExtensionAllowed;

        if (! $isExtensionAllowed && ! $isMimeAllowed) {
            abort(403, 'Tipe file tidak diizinkan.');
        }

        // Jika mime masih generic (misal application/octet-stream) tapi ekstensi pdf, paksa pdf
        if ($ext === 'pdf' && $mime !== 'application/pdf') {
            $mime = 'application/pdf';
        } elseif (in_array($ext, ['jpg','jpeg']) && ! str_contains($mime, 'jpeg')) {
            $mime = 'image/jpeg';
        } elseif ($ext === 'png' && $mime !== 'image/png') {
            $mime = 'image/png';
        }

        // Pastikan file ada sebelum dikirim
        if (! file_exists($fullPath)) {
            abort(404, 'Bukti tidak ditemukan atau file sudah tidak tersedia.');
        }

        // Untuk JPG/PNG/PDF tampilkan inline (buka di tab baru), jangan paksa download
        $originalName = $attendance->bukti_original_name ?: basename($normalized);
        // Sanitasi nama file untuk header
        $originalName = str_replace(['"', "'", "\n", "\r"], '', $originalName);

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $originalName . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Menampilkan file foto absensi (Masuk/Pulang) tanpa bergantung pada symbolic link.
     * Kompatibel dengan localhost, shared hosting, dan InfinityFree (tanpa storage:link).
     *
     * Route: GET /foto/{attendance}/lihat?tipe=masuk|pulang  -> name foto.lihat
     * Default tipe=masuk. Query ?tipe=pulang untuk foto pulang.
     * Middleware: auth (dicek di route), ditambah pengecekan role pemilik.
     */
    public function lihatFoto(Request $request, Attendance $attendance)
    {
        // ---- Autentikasi: hanya authenticated Laravel user (Tahap 4.2) ----
        $user = $request->user() ?? \Illuminate\Support\Facades\Auth::user();
        $role = $user?->role ?? $this->resolveRole($request);

        if (! $user) {
            abort(403, 'Akses ditolak. Silakan login kembali.');
        }

        // ---- Authorization: admin boleh lihat semua, siswa hanya miliknya ----
        $isAdmin = ($role === 'admin');
        $isOwner = $attendance->user_id && (int) $attendance->user_id === (int) $user->id;
        if (! $isAdmin && ! $isOwner) {
            if ($attendance->nama !== $user->nama) {
                abort(403, 'Anda tidak berhak melihat foto ini.');
            }
        }

        // Tentukan tipe foto: masuk (default) atau pulang
        $tipe = strtolower(trim((string) $request->query('tipe', $request->input('tipe', 'masuk'))));
        if (! in_array($tipe, ['masuk', 'pulang'], true)) {
            $tipe = 'masuk';
        }

        $fotoFile = $tipe === 'pulang' ? $attendance->foto_pulang : $attendance->foto;

        if (empty($fotoFile)) {
            abort(404, 'Foto tidak ditemukan atau file sudah tidak tersedia.');
        }

        // Normalisasi path: hapus prefix yang mungkin double
        $normalized = ltrim($fotoFile, '/');
        $normalized = preg_replace('#^storage/#', '', $normalized);
        $normalized = preg_replace('#^public/storage/#', '', $normalized);
        $normalized = preg_replace('#^storage/app/public/#', '', $normalized);

        // Cek file benar-benar ada di disk public
        // Hanya cek di direktori yang sesuai tipe, jangan fallback silang antara foto dan foto_pulang
        if (! Storage::disk('public')->exists($normalized)) {
            $basename = basename($normalized);
            $expectedDir = $tipe === 'pulang' ? 'absen/foto-pulang/' : 'absen/foto/';
            $fallback = $expectedDir . $basename;
            if ($basename && Storage::disk('public')->exists($fallback)) {
                $normalized = $fallback;
            } else {
                abort(404, 'Foto tidak ditemukan atau file sudah tidak tersedia.');
            }
        }

        $fullPath = Storage::disk('public')->path($normalized);
        $mime = null;
        try {
            $mime = Storage::disk('public')->mimeType($normalized);
        } catch (\Throwable $e) {
            // fallback
        }
        if (! $mime && file_exists($fullPath)) {
            $mime = @mime_content_type($fullPath);
        }
        // Fallback default untuk foto selfie (jpeg)
        if (! $mime) {
            $ext = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
            $mime = $ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg');
        }
        $mime = strtolower(trim((string) $mime));
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $ext = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
        $extAllowed = ['jpg','jpeg','png','webp'];
        $isExtensionAllowed = in_array($ext, $extAllowed, true);
        $isMimeAllowed = in_array($mime, $allowedMimes, true) || str_starts_with($mime, 'image/');
        if (! $isExtensionAllowed && ! $isMimeAllowed) {
            abort(403, 'Tipe file tidak diizinkan.');
        }
        if (in_array($ext, ['jpg','jpeg']) && ! str_contains($mime, 'jpeg')) {
            $mime = 'image/jpeg';
        } elseif ($ext === 'png' && $mime !== 'image/png') {
            $mime = 'image/png';
        } elseif ($ext === 'webp' && $mime !== 'image/webp') {
            $mime = 'image/webp';
        }

        if (! file_exists($fullPath)) {
            abort(404, 'Foto tidak ditemukan atau file sudah tidak tersedia.');
        }

        $originalName = basename($normalized);
        $originalName = str_replace(['"', "'", "\n", "\r"], '', $originalName);

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $originalName . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}