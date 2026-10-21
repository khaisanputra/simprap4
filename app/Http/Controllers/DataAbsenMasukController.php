<?php

namespace App\Http\Controllers;

use App\Exports\DataAbsenExport;
use App\Models\Attendance;
use App\Models\User;
use App\Support\Geo;
use App\Support\LokasiKantor;
use App\Support\WaktuAbsen;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DataAbsenMasukController extends Controller
{
    /**
     * Data sesi pengguna aktif - hanya authenticated Laravel user (Tahap 4.2).
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

    /**
     * Halaman monitoring Data Absen (khusus Admin).
     *
     * Seluruh data berasal dari tabel attendances (sumber yang sama dengan
     * Dashboard / Absen Masuk / Absen Pulang / Laporan). Hanya absensi yang
     * benar-benar berhasil (memiliki tgl_masuk & jam_masuk) yang ditampilkan.
     * Lokasi yang ditampilkan adalah data GPS yang tersimpan pada saat
     * absensi — bukan lokasi realtime saat Admin membuka halaman.
     */
    /**
     * Validasi & normalisasi filter Data Absen (digunakan bersama index/print/pdf/excel).
     */
    protected function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'cari'           => ['nullable', 'string', 'max:100'],
            'tanggal'        => ['nullable', 'date'],
            'tanggal_dari'   => ['nullable', 'date'],
            'tanggal_sampai' => ['nullable', 'date', 'after_or_equal:tanggal_dari'],
            'status'         => ['nullable', 'in:masuk,telat,sakit,dispen,izin'],
            'status_lokasi'  => ['nullable', 'in:valid,tidak_valid,tanpa'],
        ], [
            'tanggal_dari.date'           => 'Tanggal dari harus berupa tanggal yang valid.',
            'tanggal_sampai.date'         => 'Tanggal sampai harus berupa tanggal yang valid.',
            'tanggal_sampai.after_or_equal' => 'Tanggal sampai tidak boleh lebih kecil dari tanggal dari.',
            'tanggal.date'                => 'Tanggal harus berupa tanggal yang valid.',
        ]);

        $tanggalDari   = $validated['tanggal_dari'] ?? null;
        $tanggalSampai = $validated['tanggal_sampai'] ?? null;
        $tanggalLegacy = $validated['tanggal'] ?? null;

        $filters = [
            'cari'           => $validated['cari'] ?? null,
            'tanggal'        => $tanggalLegacy,
            'tanggal_dari'   => $tanggalDari,
            'tanggal_sampai' => $tanggalSampai,
            'status'         => $validated['status'] ?? null,
            'status_lokasi'  => $validated['status_lokasi'] ?? null,
        ];
        if (!$tanggalDari && !$tanggalSampai && $tanggalLegacy) {
            $filters['tanggal_dari']   = $tanggalLegacy;
            $filters['tanggal_sampai'] = $tanggalLegacy;
        }

        return $filters;
    }

    /**
     * Bangun query Attendance sesuai filter aktif (tanpa pagination) — dipakai bersama.
     */
    protected function buildFilteredQuery(array $filters, float $radiusSekarang)
    {
        $query = Attendance::query()
            ->with('user')
            ->whereNotNull('tgl_masuk')
            ->whereNotNull('jam_masuk');

        if ($filters['cari']) {
            $kata = '%'.trim($filters['cari']).'%';
            $query->where(function ($q) use ($kata) {
                $q->where('nama', 'LIKE', $kata)
                    ->orWhereHas('user', fn ($uq) => $uq->where('nama', 'LIKE', $kata)->orWhere('user_id', 'LIKE', $kata));
            });
        }

        $tdDari   = $filters['tanggal_dari'] ?? null;
        $tdSampai = $filters['tanggal_sampai'] ?? null;

        if ($tdDari && $tdSampai) {
            $query->whereDate('tgl_masuk', '>=', $tdDari)
                  ->whereDate('tgl_masuk', '<=', $tdSampai);
        } elseif ($tdDari) {
            $query->whereDate('tgl_masuk', '>=', $tdDari);
        } elseif ($tdSampai) {
            $query->whereDate('tgl_masuk', '<=', $tdSampai);
        } elseif ($filters['tanggal']) {
            $query->whereDate('tgl_masuk', $filters['tanggal']);
        }

        if (!empty($filters['status'])) {
            $map = [
                'masuk'  => 'Masuk',
                'telat'  => 'Telat',
                'sakit'  => 'Sakit',
                'dispen' => 'Dispensasi',
                'izin'   => 'Izin',
            ];
            $dbStatus = $map[$filters['status']] ?? null;
            if ($dbStatus) {
                if ($dbStatus === 'Masuk') {
                    $query->where(function ($q) use ($dbStatus) {
                        $q->where('status_kehadiran', $dbStatus)
                          ->orWhere(function ($q2) {
                              $q2->whereNull('status_kehadiran')->where('status', 'Masuk');
                          });
                    });
                } else {
                    $query->where('status_kehadiran', $dbStatus);
                }
            }
        }

        $query->orderByDesc('tgl_masuk')
            ->orderByDesc('jam_masuk')
            ->orderByDesc('id');

        return $query;
    }

    /**
     * Lengkapi koleksi dengan nama_tampil, jarak_tampil, radius_tampil, status_lokasi, label.
     */
    protected function enrichCollection($collection, array $lokasiKantor, float $radiusSekarang)
    {
        return $collection->transform(function ($row) use ($lokasiKantor, $radiusSekarang) {
            $row->nama_tampil = $row->user?->nama ?? $row->nama;
            $jarak = $row->jarak;
            if ($jarak === null && $row->latitude !== null && $row->longitude !== null) {
                $jarak = round(Geo::hitungJarak(
                    $row->latitude,
                    $row->longitude,
                    (float) $lokasiKantor['latitude'],
                    (float) $lokasiKantor['longitude']
                ), 2);
            }
            $isTanpaGps = in_array($row->status_kehadiran, ['Izin','Dispensasi','Sakit'], true) && $row->latitude === null;
            $radius = $row->radius_kantor_saat_absen ?? ($isTanpaGps ? null : $radiusSekarang);
            $row->jarak_tampil   = $jarak !== null ? round($jarak, 2) : null;
            $row->radius_tampil  = $radius !== null ? round($radius, 2) : null;
            $row->status_lokasi  = $jarak === null ? 'tanpa' : ($jarak <= ($radius ?? $radiusSekarang) ? 'valid' : 'tidak_valid');
            $row->status_kehadiran_label = $row->status_kehadiran ?: $row->status;
            return $row;
        });
    }

    /**
     * Teks periode untuk header print/pdf/excel.
     */
    protected function periodeText(array $filters): string
    {
        $dari = $filters['tanggal_dari'] ?? null;
        $sampai = $filters['tanggal_sampai'] ?? null;
        if ($dari && $sampai) {
            if ($dari === $sampai) {
                return \Carbon\Carbon::parse($dari)->translatedFormat('d F Y');
            }
            return \Carbon\Carbon::parse($dari)->translatedFormat('d F Y') . ' - ' . \Carbon\Carbon::parse($sampai)->translatedFormat('d F Y');
        }
        if ($dari) {
            return 'Mulai ' . \Carbon\Carbon::parse($dari)->translatedFormat('d F Y');
        }
        if ($sampai) {
            return 'Sampai ' . \Carbon\Carbon::parse($sampai)->translatedFormat('d F Y');
        }
        return 'Semua Periode';
    }

    /**
     * Nama file untuk PDF/Excel berdasarkan periode (aman untuk filesystem).
     */
    protected function exportFilename(array $filters, string $ext): string
    {
        $dari = $filters['tanggal_dari'] ?? null;
        $sampai = $filters['tanggal_sampai'] ?? null;
        if ($dari && $sampai) {
            if ($dari === $sampai) {
                return 'data-absen-' . \Carbon\Carbon::parse($dari)->format('d-m-Y') . '.' . $ext;
            }
            return 'data-absen-' . \Carbon\Carbon::parse($dari)->format('d-m-Y') . '-sampai-' . \Carbon\Carbon::parse($sampai)->format('d-m-Y') . '.' . $ext;
        }
        if ($dari) {
            return 'data-absen-mulai-' . \Carbon\Carbon::parse($dari)->format('d-m-Y') . '.' . $ext;
        }
        if ($sampai) {
            return 'data-absen-sampai-' . \Carbon\Carbon::parse($sampai)->format('d-m-Y') . '.' . $ext;
        }
        return 'data-absen-' . date('Ymd-His') . '.' . $ext;
    }

    /**
     * Tentukan tanggal target untuk perhitungan Tidak Masuk (single date).
     * Hanya single date yang didukung: tanggal_dari==tanggal_sampai atau legacy tanggal atau hari ini (tanpa filter).
     * Mengembalikan null jika rentang multi-hari atau filter status aktif.
     */
    protected function determineTargetTidakMasukTanggal(array $filters, string $tanggalHariIni, ?\DateTimeInterface $now = null): ?string
    {
        $now = $now ? \Carbon\Carbon::parse($now) : now();
        $todayStr = $now->toDateString();

        // Konsisten dengan Rekap: hanya Senin-Jumat, setelah 23:00, bukan weekend/masa depan
        // Jika target adalah hari ini dan belum lewat 23:00, jangan tampilkan virtual
        $jamSelesai = WaktuAbsen::selesai('pulang');
        // Cek apakah hari ini weekend -> tidak ada Tidak Masuk
        if (\Carbon\Carbon::parse($tanggalHariIni)->isWeekend()) {
            // Namun untuk filter tanggal lain, cek target nanti; untuk hari ini, cek dulu
            if (empty($filters['tanggal']) && empty($filters['tanggal_dari']) && empty($filters['tanggal_sampai'])) {
                return null;
            }
        }
        // Jika tidak ada filter spesifik dan hari ini belum lewat 23:00, jangan tampilkan
        $waktuSelesaiHariIni = \Carbon\Carbon::parse($todayStr . ' ' . $jamSelesai . ':00');
        $hasSpecificTarget = !empty($filters['tanggal']) || (!empty($filters['tanggal_dari']) && !empty($filters['tanggal_sampai']) && $filters['tanggal_dari'] === $filters['tanggal_sampai']);
        if (!$hasSpecificTarget && $now->lt($waktuSelesaiHariIni)) {
            return null;
        }

        // Jika filter status aktif, virtual Tidak Masuk tidak relevan (status Tidak Masuk tidak termasuk filter Masuk/Telat/Sakit/Dispen/Izin)
        if (!empty($filters['status'])) {
            return null;
        }

        // Rentang multi-hari: jangan hitung Tidak Masuk (hanya single date)
        $dari = $filters['tanggal_dari'] ?? null;
        $sampai = $filters['tanggal_sampai'] ?? null;
        $legacy = $filters['tanggal'] ?? null;

        $target = null;
        if (!empty($dari) && !empty($sampai)) {
            if ($dari === $sampai) {
                $target = $dari;
            } else {
                return null;
            }
        } elseif (!empty($dari) xor !empty($sampai)) {
            return null;
        } elseif (!empty($legacy)) {
            $target = $legacy;
        } elseif (empty($dari) && empty($sampai) && empty($legacy)) {
            $target = $tanggalHariIni;
        }

        if ($target === null) {
            return null;
        }
        // Validasi target konsisten dengan Rekap: bukan weekend, bukan masa depan, hari ini hanya setelah 23:00
        $targetCarbon = \Carbon\Carbon::parse($target);
        if ($targetCarbon->isWeekend()) {
            return null;
        }
        if ($target > $todayStr) {
            return null;
        }
        if ($target === $todayStr) {
            $waktuSelesaiHariIni = \Carbon\Carbon::parse($todayStr . ' ' . $jamSelesai . ':00');
            if ($now->lt($waktuSelesaiHariIni)) {
                return null;
            }
        }

        return $target;
    }

    /**
     * Bangun collection virtual Tidak Masuk yang sudah difilter cari dan siap digabung.
     */
    protected function buildTidakMasukVirtual(string $tanggal, array $filters, ?\DateTimeInterface $now = null): Collection
    {
        $cari = $filters['cari'] ?? null;
        return Attendance::tidakMasukVirtualUntukTanggal($tanggal, $cari, $now);
    }

    /**
     * Gabungkan real attendances (sudah difilter) dengan virtual Tidak Masuk, sort dan kembalikan.
     */
    protected function mergeRealAndVirtual(Collection $real, Collection $virtual): Collection
    {
        if ($virtual->isEmpty()) {
            return $real->values();
        }
        $merged = $real->concat($virtual);
        // Sort konsisten: nama_tampil asc, lalu id untuk stabil
        return $merged->sortBy(function ($row) {
            return strtolower($row->nama_tampil ?? $row->nama ?? '');
        })->values();
    }

    /**
     * Paginate collection merged secara manual (LengthAwarePaginator).
     */
    protected function paginateCollection(Collection $collection, Request $request, int $perPage = 10): LengthAwarePaginator
    {
        $page = (int) $request->input('page', 1);
        $page = max(1, $page);
        $total = $collection->count();
        $offset = ($page - 1) * $perPage;
        $items = $collection->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    public function index(Request $request)
    {
        $userSession     = $this->userSession($request);
        $tanggalHariIni  = now()->toDateString();

        $filters = $this->validatedFilters($request);

        $lokasiKantor   = LokasiKantor::all();
        $radiusSekarang = $lokasiKantor['radius'];

        $jumlahHariIni = Attendance::query()
            ->whereNotNull('tgl_masuk')
            ->whereNotNull('jam_masuk')
            ->padaTanggal($tanggalHariIni)
            ->count();

        // Tentukan tanggal target untuk Tidak Masuk (single date, setelah 17:00, tanpa filter status)
        $targetTidakMasukTanggal = $this->determineTargetTidakMasukTanggal($filters, $tanggalHariIni);

        if ($targetTidakMasukTanggal) {
            // Mode merged: real + virtual Tidak Masuk
            $query = $this->buildFilteredQuery($filters, $radiusSekarang);
            $realAll = $query->get();
            $this->enrichCollection($realAll, $lokasiKantor, $radiusSekarang);

            $virtual = $this->buildTidakMasukVirtual($targetTidakMasukTanggal, $filters);
            // Virtual sudah memiliki atribut tampil; pastikan tgl_masuk sebagai Carbon sudah ada

            $merged = $this->mergeRealAndVirtual($realAll, $virtual);
            // Filter status_lokasi jika diminta (Tahap 4.2: tambahan agar test filter lolos)
            if (!empty($filters['status_lokasi'])) {
                $merged = $merged->filter(fn($row) => ($row->status_lokasi ?? 'tanpa') === $filters['status_lokasi'])->values();
            }
            $items = $this->paginateCollection($merged, $request, 10);

            $details = $items->getCollection()->mapWithKeys(function ($row) use ($lokasiKantor) {
                // Virtual Tidak Masuk: foto null, bukti null, jam '-', lokasi tanpa
                $isVirtual = !empty($row->is_tidak_masuk_virtual);
                $buktiUrl = !$isVirtual && $row->bukti_file ? route('bukti.lihat', $row->id) : null;
                $fotoUrl = !$isVirtual && $row->foto ? route('foto.lihat', $row->id) : null;
                // Untuk API detail, dukung string id virtual
                $detail = [
                    'nama'              => $row->nama_tampil ?? $row->nama,
                    'tanggal'           => $row->tgl_masuk ? \Carbon\Carbon::parse($row->tgl_masuk)->format('d F Y') : '-',
                    'hari'              => $row->tgl_masuk ? \Carbon\Carbon::parse($row->tgl_masuk)->translatedFormat('l') : '-',
                    'jam_masuk'         => $row->jam_masuk ?? '-',
                    'jam_pulang'        => $row->jam_pulang ?? (in_array($row->status_kehadiran_label ?? $row->status_kehadiran ?? $row->status, ['Izin','Dispensasi','Sakit'], true) ? 'Tidak Diperlukan' : '-'),
                    'status_kehadiran'  => $row->status_kehadiran_label ?? $row->status_kehadiran ?? $row->status,
                    'keterangan'        => $row->keterangan,
                    'foto'              => $fotoUrl,
                    'bukti_file'        => $buktiUrl,
                    'bukti_url'         => $buktiUrl,
                    'bukti_original_name' => $row->bukti_original_name,
                    'bukti_mime_type'   => $row->bukti_mime_type,
                    'latitude'          => $row->latitude,
                    'longitude'         => $row->longitude,
                    'accuracy'          => $row->accuracy,
                    'jarak'             => $row->jarak_tampil ?? null,
                    'radius'            => $row->radius_tampil ?? null,
                    'status_lokasi'     => $row->status_lokasi ?? 'tanpa',
                    'kantor'            => [
                        'nama'      => $lokasiKantor['nama'],
                        'alamat'    => $lokasiKantor['alamat'],
                        'latitude'  => $lokasiKantor['latitude'],
                        'longitude' => $lokasiKantor['longitude'],
                    ],
                ];
                return [$row->id => $detail];
            });

            // Simpan virtual untuk info card (tidak duplikat, hanya summary)
            $tidakMasukUsers = $virtual;
        } else {
            $query = $this->buildFilteredQuery($filters, $radiusSekarang);
            // Jika filter status_lokasi aktif, harus filter collection setelah enrich (karena status_lokasi dihitung dari jarak/radius)
            if (!empty($filters['status_lokasi'])) {
                $all = $query->get();
                $this->enrichCollection($all, $lokasiKantor, $radiusSekarang);
                $filtered = $all->filter(fn($row) => ($row->status_lokasi ?? 'tanpa') === $filters['status_lokasi'])->values();
                $items = $this->paginateCollection($filtered, $request, 10);
            } else {
                $items = $query->paginate(10)->withQueryString();
                $this->enrichCollection($items->getCollection(), $lokasiKantor, $radiusSekarang);
            }

            $details = $items->getCollection()->mapWithKeys(function ($row) use ($lokasiKantor) {
                // Data Absen Masuk: hanya foto masuk (attendances.foto), jangan pakai foto_pulang
                $buktiUrl = $row->bukti_file ? route('bukti.lihat', $row->id) : null;
                $fotoUrl = $row->foto ? route('foto.lihat', $row->id) : null;
                $detail = [
                    'nama'              => $row->nama_tampil,
                    'tanggal'           => $row->tgl_masuk ? $row->tgl_masuk->format('d F Y') : '-',
                    'hari'              => $row->tgl_masuk ? $row->tgl_masuk->translatedFormat('l') : '-',
                    'jam_masuk'         => $row->jam_masuk,
                    'jam_pulang'        => $row->jam_pulang ?? (in_array($row->status_kehadiran_label, ['Izin','Dispensasi','Sakit'], true) ? 'Tidak Diperlukan' : '-'),
                    'status_kehadiran'  => $row->status_kehadiran_label,
                    'keterangan'        => $row->keterangan,
                    'foto'              => $fotoUrl,
                    'bukti_file'        => $buktiUrl,
                    'bukti_url'         => $buktiUrl,
                    'bukti_original_name' => $row->bukti_original_name,
                    'bukti_mime_type'   => $row->bukti_mime_type,
                    'latitude'          => $row->latitude,
                    'longitude'         => $row->longitude,
                    'accuracy'          => $row->accuracy,
                    'jarak'             => $row->jarak_tampil,
                    'radius'            => $row->radius_tampil,
                    'status_lokasi'     => $row->status_lokasi,
                    'kantor'            => [
                        'nama'      => $lokasiKantor['nama'],
                        'alamat'    => $lokasiKantor['alamat'],
                        'latitude'  => $lokasiKantor['latitude'],
                        'longitude' => $lokasiKantor['longitude'],
                    ],
                ];
                return [$row->id => $detail];
            });

            // Tidak ada Tidak Masuk (sebelum 17:00, multi-hari, atau filter status)
            $tidakMasukUsers = collect([]);
            // $targetTidakMasukTanggal sudah null dari determine di atas
        }

        return view('data-absen-masuk.index', compact(
            'userSession',
            'items',
            'filters',
            'lokasiKantor',
            'tanggalHariIni',
            'jumlahHariIni',
            'details',
            'tidakMasukUsers',
            'targetTidakMasukTanggal'
        ));
    }

    /**
     * Halaman print (tanpa pagination) — mengikuti filter aktif.
     * Menggabungkan Tidak Masuk virtual jika single date setelah 17:00.
     */
    public function print(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $lokasiKantor   = LokasiKantor::all();
        $radiusSekarang = $lokasiKantor['radius'];

        $tanggalHariIni = now()->toDateString();
        $targetTidakMasukTanggal = $this->determineTargetTidakMasukTanggal($filters, $tanggalHariIni);

        $query = $this->buildFilteredQuery($filters, $radiusSekarang);
        $items = $query->get();
        $this->enrichCollection($items, $lokasiKantor, $radiusSekarang);

        if ($targetTidakMasukTanggal) {
            $virtual = $this->buildTidakMasukVirtual($targetTidakMasukTanggal, $filters);
            $items = $this->mergeRealAndVirtual($items, $virtual);
        }
        if (!empty($filters['status_lokasi'])) {
            $items = $items->filter(fn($row) => ($row->status_lokasi ?? 'tanpa') === $filters['status_lokasi'])->values();
        }

        $periode = $this->periodeText($filters);

        return view('data-absen-masuk.print', compact('items', 'filters', 'lokasiKantor', 'periode'));
    }

    /**
     * Export Excel .xlsx (tanpa pagination) — mengikuti filter aktif.
     * Menggabungkan Tidak Masuk virtual jika single date setelah 17:00.
     */
    public function exportExcel(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $lokasiKantor   = LokasiKantor::all();
        $radiusSekarang = $lokasiKantor['radius'];

        $tanggalHariIni = now()->toDateString();
        $targetTidakMasukTanggal = $this->determineTargetTidakMasukTanggal($filters, $tanggalHariIni);

        $query = $this->buildFilteredQuery($filters, $radiusSekarang);
        $items = $query->get();
        $this->enrichCollection($items, $lokasiKantor, $radiusSekarang);

        if ($targetTidakMasukTanggal) {
            $virtual = $this->buildTidakMasukVirtual($targetTidakMasukTanggal, $filters);
            $items = $this->mergeRealAndVirtual($items, $virtual);
        }
        if (!empty($filters['status_lokasi'])) {
            $items = $items->filter(fn($row) => ($row->status_lokasi ?? 'tanpa') === $filters['status_lokasi'])->values();
        }

        $filename = $this->exportFilename($filters, 'xlsx');

        return Excel::download(new DataAbsenExport($items, $filters, $lokasiKantor), $filename);
    }
}