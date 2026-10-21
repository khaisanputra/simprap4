<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    /**
     * Relasi ke pengguna (user) yang melakukan absensi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public const STATUS_KEHADIRAN_MASUK = 'Masuk';
    public const STATUS_KEHADIRAN_TELAT = 'Telat';
    public const STATUS_KEHADIRAN_TIDAK_MASUK = 'Tidak Masuk';
    public const STATUS_KEHADIRAN_IZIN = 'Izin';
    public const STATUS_KEHADIRAN_DISPENSASI = 'Dispensasi';
    public const STATUS_KEHADIRAN_SAKIT = 'Sakit';

    public const STATUS_KEHADIRAN_LIST = [
        self::STATUS_KEHADIRAN_MASUK,
        self::STATUS_KEHADIRAN_TELAT,
        self::STATUS_KEHADIRAN_IZIN,
        self::STATUS_KEHADIRAN_DISPENSASI,
        self::STATUS_KEHADIRAN_SAKIT,
        // Tidak Masuk dihitung dinamis; tidak dikirim via form siswa
    ];

    // Untuk validasi form Masuk (siswa memilih Masuk -> backend bisa ubah jadi Telat)
    public const STATUS_KEHADIRAN_INPUT_LIST = [
        self::STATUS_KEHADIRAN_MASUK,
        self::STATUS_KEHADIRAN_IZIN,
        self::STATUS_KEHADIRAN_DISPENSASI,
        self::STATUS_KEHADIRAN_SAKIT,
    ];

    /**
     * Status yang dikecualikan dari kewajiban Absen Pulang.
     * Izin, Dispensasi, Sakit, Tidak Masuk dianggap selesai tanpa jam_pulang.
     */
    public const STATUS_KEHADIRAN_EXEMPT_PULANG = [
        self::STATUS_KEHADIRAN_IZIN,
        self::STATUS_KEHADIRAN_DISPENSASI,
        self::STATUS_KEHADIRAN_SAKIT,
        self::STATUS_KEHADIRAN_TIDAK_MASUK,
    ];

    /**
     * Status yang membutuhkan GPS + radius (Masuk & Telat).
     */
    public static function requiresGps(string $statusKehadiran): bool
    {
        return in_array($statusKehadiran, [self::STATUS_KEHADIRAN_MASUK, self::STATUS_KEHADIRAN_TELAT], true);
    }

    /**
     * Apakah status kehadiran wajib melakukan Absen Pulang?
     * Masuk & Telat wajib; Izin/Dispensasi/Sakit/Tidak Masuk tidak.
     */
    public static function requiresPulang(?string $statusKehadiran): bool
    {
        if ($statusKehadiran === null || $statusKehadiran === '') {
            return true; // legacy fallback: dianggap Masuk
        }
        return in_array($statusKehadiran, [self::STATUS_KEHADIRAN_MASUK, self::STATUS_KEHADIRAN_TELAT], true);
    }

    /**
     * Apakah status kehadiran dikecualikan dari Absen Pulang?
     */
    public static function isExemptFromPulang(?string $statusKehadiran): bool
    {
        if ($statusKehadiran === null || $statusKehadiran === '') {
            return false;
        }
        return in_array($statusKehadiran, self::STATUS_KEHADIRAN_EXEMPT_PULANG, true);
    }

    /**
     * Status kehadiran efektif (fallback ke status lama).
     */
    public function getEffectiveStatusKehadiranAttribute(): string
    {
        return $this->status_kehadiran ?: ($this->status ?: self::STATUS_KEHADIRAN_MASUK);
    }

    /**
     * Apakah absensi sudah dianggap selesai?
     * Selesai jika: sudah pulang (jam_pulang ada) ATAU status exempt (Izin/Dispensasi/Sakit/Tidak Masuk).
     */
    public function isSelesai(): bool
    {
        if ($this->jam_pulang !== null && $this->jam_pulang !== '') {
            return true;
        }
        return self::isExemptFromPulang($this->effective_status_kehadiran);
    }

    /**
     * Apakah absensi masih membutuhkan Absen Pulang?
     * True hanya untuk Masuk/Telat yang belum memiliki jam_pulang.
     */
    public function needsPulang(): bool
    {
        return self::requiresPulang($this->effective_status_kehadiran) && ($this->jam_pulang === null || $this->jam_pulang === '');
    }

    /**
     * Alias instance helper untuk cek exempt (hindari bentrok nama static).
     */
    public function isExempt(): bool
    {
        return self::isExemptFromPulang($this->effective_status_kehadiran);
    }

    /**
     * Alias instance helper untuk cek wajib pulang.
     */
    public function shouldRequirePulang(): bool
    {
        return self::requiresPulang($this->effective_status_kehadiran);
    }

    protected $fillable = [
        'user_id',
        'nama',
        'status',
        'status_kehadiran',
        'keterangan',
        'bukti_file',
        'bukti_original_name',
        'bukti_mime_type',
        'tgl_masuk',
        'jam_masuk',
        'tgl_pulang',
        'jam_pulang',
        'latitude',
        'longitude',
        'accuracy',
        'foto',
        'jarak',
        'radius_kantor_saat_absen',
        'foto_pulang',
        'latitude_pulang',
        'longitude_pulang',
        'accuracy_pulang',
        'jarak_pulang',
        'radius_kantor_saat_pulang',
    ];

    protected $casts = [
        'tgl_masuk'              => 'date',
        'jam_masuk'              => 'string',
        'tgl_pulang'             => 'date',
        'jam_pulang'             => 'string',
        'latitude'               => 'float',
        'longitude'              => 'float',
        'accuracy'               => 'float',
        'jarak'                  => 'float',
        'radius_kantor_saat_absen' => 'float',
        'latitude_pulang'        => 'float',
        'longitude_pulang'       => 'float',
        'accuracy_pulang'        => 'float',
        'jarak_pulang'           => 'float',
        'radius_kantor_saat_pulang' => 'float',
    ];

    /**
     * Scope: filter berdasarkan tanggal (kolom tgl_masuk).
     */
    public function scopePadaTanggal($query, ?string $tanggal)
    {
        if ($tanggal) {
            return $query->whereDate('tgl_masuk', $tanggal);
        }

        return $query;
    }

/**
      * Scope: pencarian berdasarkan nama, NIS (jika ada), atau status.
      */
    public function scopeCari($query, ?string $kata)
    {
        if ($kata) {
            $q = trim($kata);

            return $query->where(function ($q2) use ($q) {
                $q2->where('nama', 'LIKE', '%'.$q.'%')
                    ->orWhere('status', 'LIKE', '%'.$q.'%')
                    ->orWhere('status_kehadiran', 'LIKE', '%'.$q.'%')
                    ->orWhere('keterangan', 'LIKE', '%'.$q.'%')
                    ->orWhereHas('user', function ($uq) use ($q) {
                        $uq->where('user_id', 'LIKE', '%'.$q.'%')
                           ->orWhere('nama', 'LIKE', '%'.$q.'%')
                           ->orWhere('username', 'LIKE', '%'.$q.'%');
                    });
            });
        }

        return $query;
    }

    /**
     * Accessor: label status kehadiran untuk tampilan (fallback ke status lama).
     */
    public function getStatusKehadiranLabelAttribute(): string
    {
        return $this->status_kehadiran ?: ($this->status ?: 'Masuk');
    }

    /**
     * Daftar siswa Tidak Masuk untuk tanggal tertentu (dinamis, konsisten dengan Rekap).
     * Aturan: Senin-Jumat saja, bukan weekend, bukan masa depan, hari ini hanya setelah 23:00 WIB,
     * dan hanya jika tidak ada absensi valid (Masuk/Telat dengan pulang, Izin, Dispensasi, Sakit).
     * Mengembalikan collection User yang tidak memiliki Attendance hari itu.
     * Menangani data lama dengan user_id null via fallback nama.
     */
    public static function tidakMasukUntukTanggal(string $tanggal, ?\DateTimeInterface $waktu = null): \Illuminate\Support\Collection
    {
        $waktu = $waktu ? \Illuminate\Support\Carbon::parse($waktu) : now();
        $todayStr = $waktu->toDateString();
        $target = \Illuminate\Support\Carbon::parse($tanggal);
        // Hanya hari kerja Senin-Jumat
        if ($target->isWeekend()) {
            return collect([]);
        }
        // Masa depan bukan Alpha
        if ($tanggal > $todayStr) {
            return collect([]);
        }
        // Hari ini belum boleh Alpha sebelum 23:00 WIB (batas selesai pulang)
        if ($tanggal === $todayStr) {
            $jamSelesai = \App\Support\WaktuAbsen::selesai('pulang');
            $waktuSelesai = \Illuminate\Support\Carbon::parse($todayStr . ' ' . $jamSelesai . ':00');
            if ($waktu->lt($waktuSelesai)) {
                return collect([]);
            }
        }

        $siswaAktif = \App\Models\User::siswaAktif()->get();
        if ($siswaAktif->isEmpty()) {
            return collect([]);
        }

        // Ambil semua attendance hari itu (termasuk legacy dengan user_id null) - hanya yang VALID
        // VALID = Hadir (Masuk/Telat + jam_pulang) ATAU Izin/Dispensasi/Sakit. Masuk tanpa Pulang TIDAK valid -> dianggap Alpha setelah 23:00
        $attendances = static::query()
            ->padaTanggal($tanggal)
            ->whereNotNull('tgl_masuk')
            ->whereNotNull('jam_masuk')
            ->get(['user_id', 'nama', 'status_kehadiran', 'status', 'jam_pulang']);

        $validAttendances = $attendances->filter(function ($a) {
            $isHadir = (in_array($a->status_kehadiran, ['Masuk', 'Telat'], true)
                || ($a->status_kehadiran === null && $a->status === 'Masuk'))
                && !empty($a->jam_pulang);
            $isIzin = in_array($a->status_kehadiran, ['Izin', 'Dispensasi'], true);
            $isSakit = $a->status_kehadiran === 'Sakit';
            return $isHadir || $isIzin || $isSakit;
        });

        $hadirUserIds = $validAttendances->pluck('user_id')->filter()->unique()->toArray();
        // Nama yang hadir untuk data legacy (user_id null) - hanya valid
        $hadirNamaLegacy = $validAttendances->whereNull('user_id')->pluck('nama')->filter()->unique()->map(fn($n) => trim($n))->toArray();

        // Filter: yang tidak ada record VALID hari itu (via user_id atau fallback nama)
        return $siswaAktif->filter(function ($user) use ($hadirUserIds, $hadirNamaLegacy) {
            if (in_array($user->id, $hadirUserIds, true)) {
                return false;
            }
            // Fallback legacy: jika ada attendance valid dengan nama sama dan user_id null, anggap hadir
            if (!empty($user->nama) && in_array(trim($user->nama), $hadirNamaLegacy, true)) {
                return false;
            }
            return true;
        });
    }

    /**
     * Bangun collection virtual Attendance untuk Tidak Masuk (tidak disimpan ke DB).
     * Digunakan untuk menggabungkan ke tabel utama Data Absen.
     */
    public static function tidakMasukVirtualUntukTanggal(string $tanggal, ?string $cari = null, ?\DateTimeInterface $waktu = null): \Illuminate\Support\Collection
    {
        $usersTidakMasuk = static::tidakMasukUntukTanggal($tanggal, $waktu);
        if ($usersTidakMasuk->isEmpty()) {
            return collect([]);
        }

        // Filter cari jika ada (nama / username / User ID)
        if ($cari !== null && trim($cari) !== '') {
            $kata = strtolower(trim($cari));
            $usersTidakMasuk = $usersTidakMasuk->filter(function ($u) use ($kata) {
                return str_contains(strtolower($u->nama ?? ''), $kata)
                    || str_contains(strtolower($u->username ?? ''), $kata)
                    || str_contains(strtolower($u->user_id ?? ''), $kata);
            });
        }

        $tglCarbon = \Illuminate\Support\Carbon::parse($tanggal);

        return $usersTidakMasuk->map(function ($user) use ($tglCarbon, $tanggal) {
            $virtual = new static([
                'user_id'                  => $user->id,
                'nama'                     => $user->nama,
                'status'                   => self::STATUS_KEHADIRAN_TIDAK_MASUK,
                'status_kehadiran'         => self::STATUS_KEHADIRAN_TIDAK_MASUK,
                'keterangan'               => null,
                'bukti_file'               => null,
                'bukti_original_name'      => null,
                'bukti_mime_type'          => null,
                'tgl_masuk'                => $tglCarbon->copy(),
                'jam_masuk'                => null,
                'tgl_pulang'               => null,
                'jam_pulang'               => null,
                'latitude'                 => null,
                'longitude'                => null,
                'accuracy'                 => null,
                'foto'                     => null,
                'jarak'                    => null,
                'radius_kantor_saat_absen' => null,
                'foto_pulang'              => null,
                'latitude_pulang'          => null,
                'longitude_pulang'         => null,
                'accuracy_pulang'          => null,
                'jarak_pulang'             => null,
                'radius_kantor_saat_pulang'=> null,
            ]);
            // ID virtual string agar tidak bentrok dengan integer real
            $virtual->incrementing = false;
            $virtual->setKeyType('string');
            $virtual->setAttribute('id', 'tidak-masuk-' . $user->id . '-' . $tanggal);
            $virtual->exists = false;
            $virtual->is_tidak_masuk_virtual = true;
            // Atribut tampilan
            $virtual->nama_tampil = $user->nama ?? $user->username;
            $virtual->username_tampil = $user->username;
            $virtual->status_kehadiran_label = self::STATUS_KEHADIRAN_TIDAK_MASUK;
            $virtual->status_lokasi = 'tanpa';
            $virtual->jarak_tampil = null;
            $virtual->radius_tampil = null;
            // Relasi user untuk konsistensi
            $virtual->setRelation('user', $user);
            // Pastikan casts tgl_masuk sebagai Carbon
            $virtual->tgl_masuk = $tglCarbon->copy();
            return $virtual;
        })->values();
    }

    /**
     * Hitung jumlah Tidak Masuk (dinamis) untuk tanggal.
     */
    public static function hitungTidakMasuk(string $tanggal, ?\DateTimeInterface $waktu = null): int
    {
        return static::tidakMasukUntukTanggal($tanggal, $waktu)->count();
    }

    /**
     * Hitung total hari Tidak Masuk untuk user tertentu (siswa login).
     * Mengikuti logika Tidak Masuk yang sama: per tanggal, jika tidak ada absensi dan bukan Izin/Sakit/Dispensasi.
     * Periode: seluruh riwayat sistem (distinct tgl_masuk) hingga hari ini, konsisten dengan statistik lain (all-time).
     * Setiap tanggal dihitung 1x, menghormati batas waktu Jam Pulang Mulai untuk hari ini.
     */
    public static function hitungTidakMasukUntukUser(int $userId, ?\DateTimeInterface $now = null): int
    {
        $now = $now ? \Illuminate\Support\Carbon::parse($now) : now();
        $today = $now->toDateString();

        $user = \App\Models\User::find($userId);
        if (!$user) {
            return 0;
        }

        // Ambil semua tanggal unik di mana sistem memiliki aktivitas (distinct tgl_masuk)
        // Jika tidak ada data sama sekali, kembalikan 0
        $distinctDates = static::query()
            ->whereNotNull('tgl_masuk')
            ->whereNotNull('jam_masuk')
            ->selectRaw('DATE(tgl_masuk) as d')
            ->distinct()
            ->pluck('d')
            ->map(fn($d) => \Illuminate\Support\Carbon::parse($d)->toDateString())
            ->unique()
            ->sort()
            ->values();

        if ($distinctDates->isEmpty()) {
            return 0;
        }

        // Tentukan rentang: dari tanggal terawal sistem hingga hari ini
        // Jika user dibuat setelah tanggal terawal, mulai dari created_at user agar tidak hitung sebelum bergabung
        $earliest = $distinctDates->first();
        $userCreated = $user->created_at ? $user->created_at->toDateString() : $earliest;
        $start = $earliest > $userCreated ? $earliest : $userCreated;

        // Jika start di masa depan (user baru hari ini), tidak ada Tidak Masuk
        if ($start > $today) {
            return 0;
        }

        // Ambil semua tanggal absensi user yang VALID (Hadir + Pulang / Izin / Dispensasi / Sakit) untuk pengecekan
        // Masuk/Telat TANPA jam_pulang TIDAK dianggap valid -> setelah 23:00 menjadi Alpha
        $userRecords = static::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere(function ($q2) use ($user) {
                      $q2->whereNull('user_id')->where('nama', $user->nama);
                  });
            })
            ->whereNotNull('tgl_masuk')
            ->whereNotNull('jam_masuk')
            ->get(['tgl_masuk','status_kehadiran','status','jam_pulang']);

        // Filter hanya yang valid
        $validUserDates = $userRecords->filter(function ($r) {
            $isHadir = (in_array($r->status_kehadiran, ['Masuk','Telat'], true)
                || ($r->status_kehadiran === null && $r->status === 'Masuk'))
                && !empty($r->jam_pulang);
            $isIzin = in_array($r->status_kehadiran, ['Izin','Dispensasi'], true);
            $isSakit = $r->status_kehadiran === 'Sakit';
            return $isHadir || $isIzin || $isSakit;
        })->map(fn($r) => \Illuminate\Support\Carbon::parse($r->tgl_masuk)->toDateString())
          ->unique()->toArray();

        $userDatesSet = array_flip($validUserDates);

        $tidakMasukCount = 0;
        $jamPulangSelesai = \App\Support\WaktuAbsen::selesai('pulang');

        foreach ($distinctDates as $d) {
            // Hanya hitung tanggal dalam rentang user
            if ($d < $start || $d > $today) {
                continue;
            }
            // Hanya Senin-Jumat, weekend skip
            if (\Illuminate\Support\Carbon::parse($d)->isWeekend()) {
                continue;
            }

            // Jika user sudah memiliki status VALID hari itu (Hadir dengan Pulang / Izin / Dispensasi / Sakit), bukan Alpha
            // Masuk/Telat TANPA pulang BUKAN valid -> setelah 23:00 dihitung Alpha
            if (isset($userDatesSet[$d])) {
                continue;
            }

            // Untuk hari ini, hanya hitung jika sudah lewat Jam Pulang Selesai (23:00)
            if ($d === $today) {
                $waktuSelesaiHariIni = \Illuminate\Support\Carbon::parse($today . ' ' . $jamPulangSelesai . ':00');
                if ($now->lt($waktuSelesaiHariIni)) {
                    continue;
                }
            }

            // Tanggal tersebut tidak ada absensi user → hitung 1 Tidak Masuk
            $tidakMasukCount++;
        }

        // Tangani hari ini jika belum ada aktivitas sistem sama sekali hari ini (tidak ada di distinctDates)
        // Tetap hitung sebagai Tidak Masuk jika sudah lewat Jam Pulang Selesai dan user belum absen hari ini, dan bukan weekend/masa depan
        if (!in_array($today, $distinctDates->toArray(), true) && $today >= $start && !isset($userDatesSet[$today])) {
            if (\Illuminate\Support\Carbon::parse($today)->isWeekend()) {
                // weekend tidak dihitung
            } else {
                $waktuSelesaiHariIni = \Illuminate\Support\Carbon::parse($today . ' ' . $jamPulangSelesai . ':00');
                if ($now->gte($waktuSelesaiHariIni)) {
                    $tidakMasukCount++;
                }
            }
        }

        return $tidakMasukCount;
    }
}
