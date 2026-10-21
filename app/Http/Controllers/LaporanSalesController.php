<?php

namespace App\Http\Controllers;

use App\Exports\LaporanSalesExport;
use App\Models\LaporanSales;
use App\Models\User;
use App\Support\WaktuAbsen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class LaporanSalesController extends Controller
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
     * Build filtered query for Sales (backend filtering).
     */
    protected function buildFilteredQuery(Request $request, int $userId, string $role)
    {
        $query = LaporanSales::query()->with('user')->orderByDesc('tanggal')->orderByDesc('id');

        $isAdmin = $role === 'admin';
        if (!$isAdmin) {
            $query->where('user_id', $userId);
        }

        // Tanggal / Rentang Tanggal
        $tanggal = $request->query('tanggal');
        $tanggalDari = $request->query('tanggal_dari');
        $tanggalSampai = $request->query('tanggal_sampai');

        // Support: tanggal single OR range
        if ($tanggal) {
            $query->whereDate('tanggal', $tanggal);
        } else {
            if ($tanggalDari) {
                $query->whereDate('tanggal', '>=', $tanggalDari);
            }
            if ($tanggalSampai) {
                $query->whereDate('tanggal', '<=', $tanggalSampai);
            }
        }

        // Collector filter (admin only - by user_id or nama)
        $collector = trim((string) $request->query('collector', ''));
        if ($collector !== '') {
            // Jika numeric = user_id exact, else search nama/username
            if (is_numeric($collector) && User::where('id', $collector)->exists()) {
                $query->where('user_id', (int) $collector);
            } else {
                $query->whereHas('user', function ($q) use ($collector) {
                    $q->where('nama', 'LIKE', '%' . $collector . '%')
                      ->orWhere('username', 'LIKE', '%' . $collector . '%')
                      ->orWhere('user_id', 'LIKE', '%' . $collector . '%');
                });
            }
        }

        // For admin dropdown with collector_id param
        $collectorId = $request->query('collector_id');
        if ($collectorId !== null && $collectorId !== '') {
            $query->where('user_id', (int) $collectorId);
        }

        // Provider filter
        $provider = trim((string) $request->query('provider', ''));
        if ($provider !== '') {
            $query->where('provider', $provider);
        }

        // Cari umum (nama_customer, alamat, kelebihan/kekurangan)
        $cari = trim((string) $request->query('cari', ''));
        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('nama_customer', 'LIKE', '%' . $cari . '%')
                  ->orWhere('alamat_customer', 'LIKE', '%' . $cari . '%')
                  ->orWhere('no_telp', 'LIKE', '%' . $cari . '%')
                  ->orWhere('provider', 'LIKE', '%' . $cari . '%')
                  ->orWhere('kelebihan', 'LIKE', '%' . $cari . '%')
                  ->orWhere('kekurangan', 'LIKE', '%' . $cari . '%');
            });
        }

        return $query;
    }

    /**
     * Halaman Data Sales — Tabel + Filter (terpisah dari Form)
     */
    public function index(Request $request)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return redirect()->route('login');
        }
        $role = $this->resolveRole($request);
        $isAdmin = $role === 'admin';
        $userId = $user->id;

        $query = $this->buildFilteredQuery($request, $userId, $role);
        $laporans = $query->paginate(10)->withQueryString();

        $userSession = $this->userSession($request);

        // Daftar collector untuk dropdown filter (admin: semua siswa)
        $collectors = User::where('role', 'siswa')->orderBy('nama')->get(['id', 'nama', 'username', 'user_id']);

        // Provider list
        $providers = LaporanSales::PROVIDERS;

        return view('laporan.sales.data', compact(
            'userSession', 'user', 'laporans', 'isAdmin',
            'collectors', 'providers'
        ));
    }

    /**
     * Simpan laporan Sales baru (legacy single-customer — tidak dipakai di flow baru, tapi dipertahankan untuk kompatibilitas)
     * Redirect ke Data Sales
     */
    public function store(Request $request)
    {
        // Admin tidak boleh membuat laporan Sales
        if ($this->resolveRole($request) === 'admin') {
            return redirect()->route('laporan.sales.data')->with('sales_error', 'Admin tidak dapat membuat laporan Sales. Fitur ini hanya untuk Siswa.');
        }

        $user = $this->resolveUser($request);
        if (!$user) {
            return redirect()->route('login')->with('sales_error', 'Sesi tidak valid. Silakan login kembali.');
        }

        // Validasi jam laporan (08:00-23:59) backend
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

        $tanggalHariIni = now()->toDateString();

        $validated = $request->validate([
            'nama_customer'   => ['required', 'string', 'max:255'],
            'no_telp'         => ['nullable', 'string', 'max:30'],
            'alamat_customer' => ['required', 'string', 'max:1000'],
            'provider'        => ['required', 'string', Rule::in(LaporanSales::PROVIDERS)],
            'kelebihan'       => ['nullable', 'string', 'max:5000'],
            'kekurangan'      => ['nullable', 'string', 'max:5000'],
        ], [
            'nama_customer.required'   => 'Nama Customer wajib diisi.',
            'nama_customer.string'     => 'Nama Customer harus berupa teks.',
            'nama_customer.max'        => 'Nama Customer maksimal 255 karakter.',
            'no_telp.string'           => 'No Telp harus berupa teks.',
            'no_telp.max'              => 'No Telp maksimal 30 karakter.',
            'alamat_customer.required' => 'Alamat Customer wajib diisi.',
            'provider.required'        => 'Provider wajib dipilih.',
            'provider.in'              => 'Provider tidak valid. Pilih dari daftar yang tersedia.',
        ]);

        // Validasi kehadiran hari ini: harus sudah Absen Masuk — cek setelah validasi agar error validasi tetap prioritas
        $todayCheck = $now->toDateString();
        $attCheck = \App\Models\Attendance::query()
            ->padaTanggal($todayCheck)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere(function ($q2) use ($user) {
                      $q2->whereNull('user_id')->where('nama', $user->nama);
                  });
            })
            ->latest('id')
            ->first();
        if (! $attCheck || ! $attCheck->jam_masuk) {
            return back()->withInput()->with('sales_error', 'Anda harus melakukan Absen Masuk terlebih dahulu sebelum membuat laporan. Silakan lakukan Absen Masuk hari ini.');
        }

        try {
            $laporan = LaporanSales::create([
                'user_id'         => $user->id,
                'tanggal'         => $tanggalHariIni,
                'nama_customer'   => trim($validated['nama_customer']),
                'no_telp'         => isset($validated['no_telp']) ? trim($validated['no_telp']) : null,
                'alamat_customer' => trim($validated['alamat_customer']),
                'provider'        => $validated['provider'],
                'kelebihan'       => isset($validated['kelebihan']) ? trim($validated['kelebihan']) : null,
                'kekurangan'      => isset($validated['kekurangan']) ? trim($validated['kekurangan']) : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan laporan Sales', ['user_id' => $user->id, 'msg' => $e->getMessage()]);
            return back()->withInput()->with('sales_error', 'Gagal menyimpan laporan Sales. Silakan coba lagi.');
        }

        return redirect()->route('laporan.sales.data')->with('sales_success', 'Laporan Sales berhasil disimpan.');
    }

    /**
     * Detail laporan Sales
     */
    public function show(Request $request, LaporanSales $sales)
    {
        $user = $this->resolveUser($request);
        if (!$user) abort(403);
        $role = $this->resolveRole($request);
        $isAdmin = $role === 'admin';
        if (!$isAdmin && (int) $sales->user_id !== (int) $user->id) {
            abort(403, 'Anda tidak berhak melihat laporan ini.');
        }
        $sales->load('user');
        $userSession = $this->userSession($request);
        return view('laporan.sales.show', compact('userSession', 'sales', 'isAdmin'));
    }

    /**
     * Hapus laporan Sales
     */
    public function destroy(Request $request, LaporanSales $sales)
    {
        $user = $this->resolveUser($request);
        if (!$user) abort(403);
        $role = $this->resolveRole($request);
        $isAdmin = $role === 'admin';
        if (!$isAdmin && (int) $sales->user_id !== (int) $user->id) {
            abort(403, 'Anda tidak berhak menghapus laporan ini.');
        }
        $sales->delete();
        return redirect()->route('laporan.sales.data')->with('sales_success', 'Laporan Sales berhasil dihapus.');
    }

    /**
     * Export Excel Sales mengikuti filter aktif
     */
    public function exportExcel(Request $request)
    {
        $user = $this->resolveUser($request);
        if (!$user) abort(403);
        $role = $this->resolveRole($request);
        $userId = $user->id;

        // Reuse same filter logic but without pagination — get all
        $query = $this->buildFilteredQuery($request, $userId, $role);
        $items = $query->get();

        // No otomatis berdasarkan urutan tampil (orderBy tanggal desc, id desc)
        $filename = 'laporan-sales-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new LaporanSalesExport($items), $filename);
    }
}
