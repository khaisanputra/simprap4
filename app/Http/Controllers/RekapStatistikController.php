<?php

namespace App\Http\Controllers;

use App\Exports\RekapCalendarExport;
use App\Models\MonthlyStatistic;
use App\Models\User;
use App\Support\MonthlyStatisticService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class RekapStatistikController extends Controller
{
    /**
     * Ambil role dari authenticated Laravel user saja (Tahap 4.2).
     */
    protected function resolveRole(Request $request): string
    {
        $user = $request->user() ?? Auth::user();
        return $user?->role ?? 'siswa';
    }

    protected function resolveUserId(Request $request): ?int
    {
        $user = $request->user() ?? Auth::user();
        if ($user) {
            return (int) $user->id;
        }
        return Auth::id() !== null ? (int) Auth::id() : null;
    }

    /**
     * Halaman Rekap Absen siswa: menampilkan rekap milik siswa sendiri dengan 3 jenis rentang.
     * Siswa hanya dapat melihat data miliknya, tidak dapat melihat siswa lain.
     */
    public function siswaIndex(Request $request)
    {
        $role = $this->resolveRole($request);
        $userId = $this->resolveUserId($request);

        if (!$userId) {
            abort(403, 'Sesi tidak valid. Silakan login kembali.');
        }

        if ($role === 'admin') {
            // Admin tetap boleh lihat miliknya
        }

        try {
            MonthlyStatisticService::ensureArchivedForUser($userId);
        } catch (\Throwable $e) {}

        $userAuth = $request->user() ?? Auth::user();
        $username = $userAuth?->username ?? $userAuth?->nama ?? null;
        $user = User::find($userId);

        $validated = $request->validate([
            'jenis' => ['nullable','in:mingguan,bulanan,periode'],
            'bulan' => ['nullable','integer','min:1','max:12'],
            'tahun' => ['nullable','integer','min:2020','max:2100'],
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date','after_or_equal:start_date'],
            'dari' => ['nullable','date'],
            'sampai' => ['nullable','date','after_or_equal:dari'],
            'tanggal' => ['nullable','date'],
            'tahun_filter' => ['nullable','integer','min:2020','max:2100'],
        ], [
            'end_date.after_or_equal' => 'Tanggal sampai tidak boleh lebih kecil dari tanggal dari.',
            'sampai.after_or_equal' => 'Tanggal sampai tidak boleh lebih kecil dari tanggal dari.',
        ]);

        $jenis = $validated['jenis'] ?? 'bulanan';
        $now = now();
        $labelHeader = 'Rekap Absen';
        $isLive = false;

        if ($jenis === 'mingguan') {
            $tanggalInput = $validated['tanggal'] ?? $validated['start_date'] ?? $validated['dari'] ?? $now->toDateString();
            $monday = \Illuminate\Support\Carbon::parse($tanggalInput)->startOfWeek(\Illuminate\Support\Carbon::MONDAY);
            $sunday = $monday->copy()->endOfWeek(\Illuminate\Support\Carbon::SUNDAY);
            $start = $monday->copy()->startOfDay();
            $end = $sunday->copy()->endOfDay();
            $labelPeriode = $start->translatedFormat('j F Y') . ' - ' . $end->translatedFormat('j F Y');
            $labelRange = $labelPeriode;
            $labelHeader = 'Rekap Mingguan';
            $isLive = $end->gte($now->copy()->startOfDay()) && $start->lte($now->copy()->endOfDay());
            $bulan = (int) $start->format('n');
            $tahun = (int) $start->format('Y');
            $start_date = $start->toDateString();
            $end_date = $end->toDateString();
            $tanggal = $monday->toDateString();
        } elseif ($jenis === 'periode') {
            $dariRaw = $validated['start_date'] ?? $validated['dari'] ?? null;
            $sampaiRaw = $validated['end_date'] ?? $validated['sampai'] ?? null;
            if (!$dariRaw) $dariRaw = $now->copy()->startOfMonth()->toDateString();
            if (!$sampaiRaw) $sampaiRaw = $now->toDateString();
            $start = \Illuminate\Support\Carbon::parse($dariRaw)->startOfDay();
            $end = \Illuminate\Support\Carbon::parse($sampaiRaw)->endOfDay();
            $labelPeriode = $start->translatedFormat('d F Y') . ' - ' . $end->translatedFormat('d F Y');
            $labelRange = $labelPeriode;
            $labelHeader = 'Rekap Periode';
            $isLive = $end->gte($now->copy()->startOfDay()) && $start->lte($now->copy()->endOfDay());
            $bulan = (int) $start->format('n');
            $tahun = (int) $start->format('Y');
            $start_date = $start->toDateString();
            $end_date = $end->toDateString();
            $tanggal = $start->toDateString();
        } else {
            $jenis = 'bulanan';
            $bulan = $validated['bulan'] ?? (int) $now->format('n');
            $tahun = $validated['tahun'] ?? (int) $now->format('Y');
            $start = \Illuminate\Support\Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
            $end = $start->copy()->endOfMonth()->endOfDay();
            $labelPeriode = \App\Models\MonthlyStatistic::namaBulan($bulan).' '.$tahun;
            $labelRange = $start->translatedFormat('d F Y') . ' - ' . $end->translatedFormat('d F Y');
            $labelHeader = 'Rekap Absen';
            $isLive = ($bulan === (int) $now->format('n') && $tahun === (int) $now->format('Y'));
            $start_date = $start->toDateString();
            $end_date = $end->toDateString();
            $tanggal = $start->toDateString();
        }

        // Hitung rekap untuk periode terpilih (single user)
        try {
            $data = MonthlyStatisticService::hitungUntukUserPeriode($userId, $start, $end);
            $rekap = new MonthlyStatistic($data);
            $rekap->user_id = $userId;
            $rekap->bulan = (int) $start->format('n');
            $rekap->tahun = (int) $start->format('Y');
            $rekap->start_date = $start->toDateString();
            $rekap->end_date = $end->toDateString();
            $rekap->setRelation('user', $user);
            $rekap->exists = false;
        } catch (\Throwable $e) {
            $rekap = new MonthlyStatistic(['bulan'=>$bulan,'tahun'=>$tahun,'jumlah_hadir'=>0,'jumlah_izin'=>0,'jumlah_sakit'=>0,'jumlah_alpha'=>0,'jumlah_laporan'=>0,'start_date'=>$start->toDateString(),'end_date'=>$end->toDateString()]);
            $rekap->setRelation('user', $user);
        }

        // Untuk kompatibilitas history lama: tetap sediakan arsip & tahunList tapi tidak dipakai utama
        $filterTahun = $validated['tahun_filter'] ?? $validated['tahun'] ?? null;
        $query = MonthlyStatistic::untukUser($userId)->terbaru();
        if ($filterTahun) $query->where('tahun', $filterTahun);
        $arsip = $query->paginate(12)->withQueryString();
        $tahunList = MonthlyStatistic::untukUser($userId)->selectRaw('tahun')->distinct()->orderByDesc('tahun')->pluck('tahun');
        if ($tahunList->isEmpty()) $tahunList = collect([(int) $now->format('Y')]);
        $live = null;
        try {
            $live = MonthlyStatisticService::getStatistikBulanBerjalan($userId);
            $live['label'] = MonthlyStatistic::namaBulan($live['bulan']).' '.$live['tahun'];
        } catch (\Throwable $e) {}

        // === KALENDER: tanggalList + matrix per tanggal ===
        $tanggalListSiswa = collect();
        $matrixSiswa = [];
        $totalsSiswa = [];
        try {
            $usersSiswa = collect([$user]);
            $kalSiswa = MonthlyStatisticService::kalenderUntukUsers($usersSiswa, $start, $end, $now);
            $tanggalListSiswa = $kalSiswa['tanggalList'];
            $matrixSiswa = $kalSiswa['matrix'];
            $totalsSiswa = $kalSiswa['totals'];
        } catch (\Throwable $e) {
            $tanggalListSiswa = MonthlyStatisticService::tanggalList($start, $end);
            $matrixSiswa = [];
            $totalsSiswa = [];
        }

        return view('rekap.siswa-arsip', compact('rekap','arsip','tahunList','filterTahun','username','role','live','userId','user','jenis','bulan','tahun','start_date','end_date','tanggal','start','end','labelPeriode','labelRange','labelHeader','isLive','tanggalListSiswa','matrixSiswa','totalsSiswa'));
    }

    /**
     * Halaman rekap admin: Rekap Absen dengan 3 jenis rentang (mingguan/bulanan/periode).
     * Menggunakan start_date & end_date sebagai dasar perhitungan unified.
     */
    public function adminIndex(Request $request)
    {
        $role = $this->resolveRole($request);
        if ($role !== 'admin') {
            abort(403, 'HOnly admin yang dapat mengakses halaman ini.');
        }

        $userAuth2 = $request->user() ?? Auth::user();
        $username = $userAuth2?->username ?? $userAuth2?->nama ?? null;

        $validated = $request->validate([
            'cari' => ['nullable','string','max:100'],
            'jenis' => ['nullable','in:mingguan,bulanan,periode'],
            'bulan' => ['nullable','integer','min:1','max:12'],
            'tahun' => ['nullable','integer','min:2020','max:2100'],
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date','after_or_equal:start_date'],
            'dari' => ['nullable','date'],
            'sampai' => ['nullable','date','after_or_equal:dari'],
            'tanggal' => ['nullable','date'],
        ], [
            'end_date.after_or_equal' => 'Tanggal sampai tidak boleh lebih kecil dari tanggal dari.',
            'sampai.after_or_equal' => 'Tanggal sampai tidak boleh lebih kecil dari tanggal dari.',
        ]);

        $cari = trim($validated['cari'] ?? '');
        $jenis = $validated['jenis'] ?? 'bulanan';

        // Tentukan rentang start & end berdasarkan jenis
        $now = now();
        $isBulanBerjalan = false;
        $isLive = false;
        $labelPeriode = '';
        $labelRange = '';
        $labelHeader = 'Rekap Absen';

        if ($jenis === 'mingguan') {
            $tanggalInput = $validated['tanggal'] ?? $validated['start_date'] ?? $validated['dari'] ?? $now->toDateString();
            $monday = \Illuminate\Support\Carbon::parse($tanggalInput)->startOfWeek(\Illuminate\Support\Carbon::MONDAY);
            $sunday = $monday->copy()->endOfWeek(\Illuminate\Support\Carbon::SUNDAY);
            $start = $monday->copy()->startOfDay();
            $end = $sunday->copy()->endOfDay();
            $labelPeriode = $start->translatedFormat('j F Y') . ' - ' . $end->translatedFormat('j F Y');
            $labelRange = $labelPeriode;
            $labelHeader = 'Rekap Mingguan';
            $isLive = $end->gte($now->copy()->startOfDay()) && $start->lte($now->copy()->endOfDay());
            // untuk view bulanan fallback
            $bulan = (int) $start->format('n');
            $tahun = (int) $start->format('Y');
            $start_date = $start->toDateString();
            $end_date = $end->toDateString();
            $tanggal = $monday->toDateString();
        } elseif ($jenis === 'periode') {
            $dariRaw = $validated['start_date'] ?? $validated['dari'] ?? null;
            $sampaiRaw = $validated['end_date'] ?? $validated['sampai'] ?? null;
            if (!$dariRaw) $dariRaw = $now->copy()->startOfMonth()->toDateString();
            if (!$sampaiRaw) $sampaiRaw = $now->toDateString();
            $start = \Illuminate\Support\Carbon::parse($dariRaw)->startOfDay();
            $end = \Illuminate\Support\Carbon::parse($sampaiRaw)->endOfDay();
            $labelPeriode = $start->translatedFormat('d F Y') . ' - ' . $end->translatedFormat('d F Y');
            $labelRange = $labelPeriode;
            $labelHeader = 'Rekap Periode';
            $isLive = $end->gte($now->copy()->startOfDay()) && $start->lte($now->copy()->endOfDay());
            $bulan = (int) $start->format('n');
            $tahun = (int) $start->format('Y');
            $start_date = $start->toDateString();
            $end_date = $end->toDateString();
            $tanggal = $start->toDateString();
        } else {
            // bulanan
            $jenis = 'bulanan';
            $bulan = $validated['bulan'] ?? (int) $now->format('n');
            $tahun = $validated['tahun'] ?? (int) $now->format('Y');
            $start = \Illuminate\Support\Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
            $end = $start->copy()->endOfMonth()->endOfDay();
            $labelPeriode = \App\Models\MonthlyStatistic::namaBulan($bulan).' '.$tahun;
            $labelRange = $start->translatedFormat('d F Y') . ' - ' . $end->translatedFormat('d F Y');
            $labelHeader = 'Rekap Absen';
            $isBulanBerjalan = ($bulan === (int) $now->format('n') && $tahun === (int) $now->format('Y'));
            $isLive = $isBulanBerjalan;
            $start_date = $start->toDateString();
            $end_date = $end->toDateString();
            $tanggal = $start->toDateString();
        }

        // Ambil daftar siswa aktif (dengan filter cari termasuk User ID), pagination
        $siswaQuery = User::siswaAktif();
        if ($cari !== '') {
            $siswaQuery->where(function($q) use ($cari){
                $q->where('nama','LIKE','%'.$cari.'%')->orWhere('username','LIKE','%'.$cari.'%')->orWhere('user_id','LIKE','%'.$cari.'%');
            });
        }
        // Urutkan berdasarkan users.user_id ASC (G1 - INT0030, INT0031, dst) - bukan nama alfabet
        $siswaQuery->orderBy('user_id','asc');
        $siswaPage = $siswaQuery->paginate(20)->withQueryString();

        // Hitung rekap per siswa berdasarkan jenis
        $rekap = collect();
        foreach ($siswaPage as $siswa) {
            try {
                if ($jenis === 'bulanan') {
                    if ($isBulanBerjalan) {
                        $data = MonthlyStatisticService::hitungUntukUserPeriode($siswa->id, $start, $end);
                    } else {
                        // Arsip bulanan: persist agar konsisten
                        $data = MonthlyStatisticService::hitungUntukUserPeriode($siswa->id, $start, $end);
                        // Juga update snapshot bulanan untuk backfill
                        MonthlyStatistic::updateOrCreate(
                            ['user_id'=>$siswa->id,'bulan'=>$bulan,'tahun'=>$tahun],
                            $data
                        );
                    }
                } else {
                    // Mingguan & Periode selalu live per periode
                    $data = MonthlyStatisticService::hitungUntukUserPeriode($siswa->id, $start, $end);
                }
                $row = new MonthlyStatistic($data);
                $row->user_id = $siswa->id;
                $row->bulan = (int) $start->format('n');
                $row->tahun = (int) $start->format('Y');
                // simpan range untuk view jika diperlukan
                $row->start_date = $start->toDateString();
                $row->end_date = $end->toDateString();
                $row->setRelation('user', $siswa);
                $row->exists = false;
            } catch (\Throwable $e) {
                $row = new MonthlyStatistic([
                    'bulan'=>$bulan ?? (int) $start->format('n'),
                    'tahun'=>$tahun ?? (int) $start->format('Y'),
                    'jumlah_masuk'=>0,'jumlah_pulang'=>0,'jumlah_izin'=>0,'jumlah_sakit'=>0,'jumlah_dispensasi'=>0,'jumlah_telat'=>0,'jumlah_tidak_masuk'=>0,'persentase_kehadiran'=>0,'jumlah_hadir'=>0,'jumlah_alpha'=>0,'jumlah_laporan'=>0,
                    'start_date'=>$start->toDateString(),'end_date'=>$end->toDateString()
                ]);
                $row->user_id = $siswa->id;
                $row->setRelation('user', $siswa);
                $row->exists = false;
            }
            $rekap->push($row);
        }

        // Daftar tahun untuk filter bulanan (dari arsip + tahun berjalan)
        $tahunList = MonthlyStatistic::selectRaw('tahun')->distinct()->orderByDesc('tahun')->pluck('tahun');
        $currentYearForList = (int) $now->format('Y');
        if (isset($tahun) && !$tahunList->contains($tahun)) {
            $tahunList->push($tahun);
            $tahunList = $tahunList->sortDesc()->values();
        }
        if ($tahunList->isEmpty()) {
            $tahunList = collect([$currentYearForList]);
        }

        // === KALENDER: build tanggalList + matrix + totals (sekali query untuk halaman ini) ===
        $tanggalList = collect();
        $matrix = [];
        $totalsKalender = [];
        try {
            $kal = MonthlyStatisticService::kalenderUntukUsers($siswaPage->getCollection(), $start, $end, $now);
            $tanggalList = $kal['tanggalList'];
            $matrix = $kal['matrix'];
            $totalsKalender = $kal['totals'];
        } catch (\Throwable $e) {
            $tanggalList = MonthlyStatisticService::tanggalList($start, $end);
            $matrix = [];
            $totalsKalender = [];
        }

        return view('rekap.admin-index', compact('rekap','siswaPage','cari','bulan','tahun','tahunList','labelPeriode','labelRange','labelHeader','username','role','isBulanBerjalan','isLive','jenis','start_date','end_date','tanggal','start','end','tanggalList','matrix','totalsKalender'));
    }

    /**
     * Detail klik per tanggal (AJAX) untuk modal.
     */
    public function detail(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required','integer','exists:users,id'],
            'tanggal' => ['required','date'],
        ]);
        // Auth check: siswa hanya boleh lihat miliknya, admin boleh semua
        $role = $this->resolveRole($request);
        $authId = $this->resolveUserId($request);
        if ($role !== 'admin' && (int)$validated['user_id'] !== (int)$authId) {
            abort(403, 'Tidak berhak melihat detail siswa lain.');
        }
        $detail = MonthlyStatisticService::detailUntukTanggal((int)$validated['user_id'], $validated['tanggal']);
        return response()->json($detail);
    }

    /**
     * Export Excel kalender rekap (admin).
     */
    public function exportExcelCalendar(Request $request)
    {
        $role = $this->resolveRole($request);
        if ($role !== 'admin') abort(403);
        $validated = $request->validate([
            'cari' => ['nullable','string','max:100'],
            'jenis' => ['nullable','in:mingguan,bulanan,periode'],
            'bulan' => ['nullable','integer','min:1','max:12'],
            'tahun' => ['nullable','integer','min:2020','max:2100'],
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date','after_or_equal:start_date'],
            'dari' => ['nullable','date'],
            'sampai' => ['nullable','date','after_or_equal:dari'],
            'tanggal' => ['nullable','date'],
        ]);
        $cari = trim($validated['cari'] ?? '');
        $jenis = $validated['jenis'] ?? 'bulanan';
        $now = now();
        if ($jenis === 'mingguan') {
            $tanggalInput = $validated['tanggal'] ?? $validated['start_date'] ?? $validated['dari'] ?? $now->toDateString();
            $monday = \Illuminate\Support\Carbon::parse($tanggalInput)->startOfWeek(\Illuminate\Support\Carbon::MONDAY);
            $sunday = $monday->copy()->endOfWeek(\Illuminate\Support\Carbon::SUNDAY);
            $start = $monday->copy()->startOfDay(); $end = $sunday->copy()->endOfDay();
            $labelPeriode = $start->translatedFormat('j F Y').' - '.$end->translatedFormat('j F Y');
        } elseif ($jenis === 'periode') {
            $dariRaw = $validated['start_date'] ?? $validated['dari'] ?? $now->copy()->startOfMonth()->toDateString();
            $sampaiRaw = $validated['end_date'] ?? $validated['sampai'] ?? $now->toDateString();
            $start = \Illuminate\Support\Carbon::parse($dariRaw)->startOfDay(); $end = \Illuminate\Support\Carbon::parse($sampaiRaw)->endOfDay();
            $labelPeriode = $start->translatedFormat('d F Y').' - '.$end->translatedFormat('d F Y');
        } else {
            $bulan = $validated['bulan'] ?? (int)$now->format('n');
            $tahun = $validated['tahun'] ?? (int)$now->format('Y');
            $start = \Illuminate\Support\Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
            $end = $start->copy()->endOfMonth()->endOfDay();
            $labelPeriode = MonthlyStatistic::namaBulan($bulan).' '.$tahun;
        }
        $q = User::siswaAktif()->orderBy('user_id','asc');
        if ($cari !== '') $q->where(function($qq) use ($cari){ $qq->where('nama','LIKE','%'.$cari.'%')->orWhere('username','LIKE','%'.$cari.'%')->orWhere('user_id','LIKE','%'.$cari.'%'); });
        $users = $q->get();
        $tanggalList = MonthlyStatisticService::tanggalList($start, $end);
        $kal = MonthlyStatisticService::kalenderUntukUsers($users, $start, $end, $now);
        $filename = 'rekap-kalender-'.str_replace([' ','/'],'-',$labelPeriode).'-'.date('Ymd-His').'.xlsx';
        return Excel::download(new RekapCalendarExport($users, $tanggalList, $kal['matrix'], $kal['totals'], $labelPeriode, $jenis), $filename);
    }

    /**
     * Print kalender rekap (admin) — view siap cetak.
     */
    public function printCalendar(Request $request)
    {
        $role = $this->resolveRole($request);
        if ($role !== 'admin') abort(403);
        $validated = $request->validate([
            'cari' => ['nullable','string','max:100'],
            'jenis' => ['nullable','in:mingguan,bulanan,periode'],
            'bulan' => ['nullable','integer','min:1','max:12'],
            'tahun' => ['nullable','integer','min:2020','max:2100'],
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date','after_or_equal:start_date'],
            'dari' => ['nullable','date'],
            'sampai' => ['nullable','date','after_or_equal:dari'],
            'tanggal' => ['nullable','date'],
        ]);
        $cari = trim($validated['cari'] ?? '');
        $jenis = $validated['jenis'] ?? 'bulanan';
        $now = now();
        if ($jenis === 'mingguan') {
            $tanggalInput = $validated['tanggal'] ?? $validated['start_date'] ?? $validated['dari'] ?? $now->toDateString();
            $monday = \Illuminate\Support\Carbon::parse($tanggalInput)->startOfWeek(\Illuminate\Support\Carbon::MONDAY);
            $sunday = $monday->copy()->endOfWeek(\Illuminate\Support\Carbon::SUNDAY);
            $start = $monday->copy()->startOfDay(); $end = $sunday->copy()->endOfDay();
            $labelPeriode = $start->translatedFormat('j F Y').' - '.$end->translatedFormat('j F Y');
        } elseif ($jenis === 'periode') {
            $dariRaw = $validated['start_date'] ?? $validated['dari'] ?? $now->copy()->startOfMonth()->toDateString();
            $sampaiRaw = $validated['end_date'] ?? $validated['sampai'] ?? $now->toDateString();
            $start = \Illuminate\Support\Carbon::parse($dariRaw)->startOfDay(); $end = \Illuminate\Support\Carbon::parse($sampaiRaw)->endOfDay();
            $labelPeriode = $start->translatedFormat('d F Y').' - '.$end->translatedFormat('d F Y');
        } else {
            $bulan = $validated['bulan'] ?? (int)$now->format('n');
            $tahun = $validated['tahun'] ?? (int)$now->format('Y');
            $start = \Illuminate\Support\Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
            $end = $start->copy()->endOfMonth()->endOfDay();
            $labelPeriode = MonthlyStatistic::namaBulan($bulan).' '.$tahun;
        }
        $q = User::siswaAktif()->orderBy('user_id','asc');
        if ($cari !== '') $q->where(function($qq) use ($cari){ $qq->where('nama','LIKE','%'.$cari.'%')->orWhere('username','LIKE','%'.$cari.'%')->orWhere('user_id','LIKE','%'.$cari.'%'); });
        $users = $q->get();
        $kal = MonthlyStatisticService::kalenderUntukUsers($users, $start, $end, $now);
        return view('rekap.print-calendar', [
            'users' => $users,
            'tanggalList' => $kal['tanggalList'],
            'matrix' => $kal['matrix'],
            'totals' => $kal['totals'],
            'labelPeriode' => $labelPeriode,
            'jenis' => $jenis,
            'start' => $start,
            'end' => $end,
        ]);
    }
}
