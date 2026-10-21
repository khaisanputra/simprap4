<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\LaporanSales;
use App\Models\User;
use App\Support\WaktuAbsen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class LaporanSalesCollectController extends Controller
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
     * Halaman Collect Data Sales — 1 sampai 10 data customer
     */
    public function index(Request $request, Laporan $laporan)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return redirect()->route('login');
        }
        $role = $this->resolveRole($request);
        $isAdmin = $role === 'admin';

        // Authorization: hanya pemilik laporan yang boleh collect (admin tidak boleh membuat tapi boleh lihat? spec: admin tidak membuat, tapi untuk keamanan kita batasi pemilik)
        // Jika admin mencoba akses collect halaman, tampilkan tapi disable submit (atau redirect). Disini kita izinkan admin lihat tapi tidak submit.
        // Namun spec lebih ketat: admin tidak boleh membuat laporan Sales, jadi jika admin akses collect kita tolak.
        if (!$isAdmin && (int) $laporan->user_id !== (int) $user->id) {
            abort(403, 'Anda tidak berhak mengakses laporan ini.');
        }
        if ($isAdmin) {
            // Admin tidak boleh collect, redirect ke Data Sales dengan pesan
            return redirect()->route('laporan.sales.data')->with('sales_error', 'Admin tidak dapat menambah data Collect Sales.');
        }

        // Validasi Sales parent: hanya laporan dengan jenis_laporan = sales
        if ($laporan->jenis_laporan !== \App\Models\Laporan::JENIS_SALES) {
            abort(403, 'Laporan ini bukan laporan Sales. Sales Collect hanya untuk laporan Sales.');
        }

        $laporan->load(['user','fotos','sales']);

        $userSession = $this->userSession($request);
        $providers = LaporanSales::PROVIDERS;
        $jamLaporan = WaktuAbsen::all('laporan');
        $jamDibuka = WaktuAbsen::dibuka(now(), 'laporan');

        return view('laporan.sales.collect', compact('userSession', 'user', 'laporan', 'isAdmin', 'providers', 'jamLaporan', 'jamDibuka'));
    }

    /**
     * Simpan 1-10 data customer ke laporan_sales dengan transaction
     */
    public function store(Request $request, Laporan $laporan)
    {
        if ($this->resolveRole($request) === 'admin') {
            return redirect()->route('laporan.sales.data')->with('sales_error', 'Admin tidak dapat membuat laporan Sales. Fitur ini hanya untuk Siswa.');
        }

        $user = $this->resolveUser($request);
        if (!$user) {
            return redirect()->route('login')->with('sales_error', 'Sesi tidak valid. Silakan login kembali.');
        }

        if ((int) $laporan->user_id !== (int) $user->id) {
            abort(403, 'Anda tidak berhak menambah data untuk laporan ini.');
        }

        // Validasi Sales parent: harus jenis_laporan = sales
        if ($laporan->jenis_laporan !== \App\Models\Laporan::JENIS_SALES) {
            abort(403, 'Laporan ini bukan laporan Sales. Sales Collect hanya untuk laporan Sales.');
        }

        // Validasi jam laporan
        $now = now();
        if (!WaktuAbsen::dibuka($now, 'laporan')) {
            $jam = WaktuAbsen::all('laporan');
            $status = WaktuAbsen::status($now, 'laporan');
            if ($status === 'belum') {
                return back()->withInput()->with('sales_error', 'Collect Data Sales belum dibuka. Jam laporan: ' . $jam['mulai'] . ' - ' . $jam['selesai'] . ' WIB.');
            } else {
                return back()->withInput()->with('sales_error', 'Jam laporan telah berakhir. Jam laporan: ' . $jam['mulai'] . ' - ' . $jam['selesai'] . ' WIB.');
            }
        }

        $validated = $request->validate([
            'customers' => ['required', 'array', 'min:1', 'max:10'],
            'customers.*.nama_customer'   => ['required', 'string', 'max:255'],
            'customers.*.no_telp'         => ['nullable', 'string', 'max:30'],
            'customers.*.alamat_customer' => ['required', 'string', 'max:1000'],
            'customers.*.provider'        => ['required', 'string', Rule::in(LaporanSales::PROVIDERS)],
            'customers.*.kelebihan'       => ['nullable', 'string', 'max:5000'],
            'customers.*.kekurangan'      => ['nullable', 'string', 'max:5000'],
        ], [
            'customers.required' => 'Minimal 1 data customer wajib diisi.',
            'customers.min' => 'Minimal 1 data customer wajib diisi.',
            'customers.max' => 'Maksimal 10 data customer dalam satu submit.',
            'customers.*.nama_customer.required' => 'Nama Customer wajib diisi.',
            'customers.*.alamat_customer.required' => 'Alamat Customer wajib diisi.',
            'customers.*.provider.required' => 'Provider wajib dipilih.',
            'customers.*.provider.in' => 'Provider tidak valid. Pilih dari daftar yang tersedia.',
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
            return back()->withInput()->with('sales_error', 'Anda harus melakukan Absen Masuk terlebih dahulu sebelum menambah data customer. Silakan lakukan Absen Masuk hari ini.');
        }

        $customers = $validated['customers'];
        $count = count($customers);
        if ($count < 1 || $count > 10) {
            return back()->withInput()->with('sales_error', 'Jumlah data customer harus antara 1 sampai 10. Anda mengirim ' . $count . ' data.');
        }

        // Validasi akumulasi per laporan_id (maks 10) — cegah bypass via multi-submit.
        // Cek awal di sini agar pesan jelas; cek ulang dengan lock di dalam
        // transaksi untuk request yang masuk hampir bersamaan.
        $existingCount = LaporanSales::where('laporan_id', $laporan->id)->count();
        if ($existingCount + $count > 10) {
            return back()->withInput()->with('sales_error', 'Maksimal 10 data customer per laporan. Sudah ada ' . $existingCount . ' data, Anda mencoba menambah ' . $count . ' lagi (total ' . ($existingCount + $count) . ').');
        }

        // Tanggal untuk laporan_sales: gunakan tanggal_laporan dari laporan utama agar konsisten
        $tanggal = $laporan->tanggal_laporan ? $laporan->tanggal_laporan->toDateString() : now()->toDateString();

        DB::beginTransaction();
        try {
            // Proteksi concurrency: kunci baris laporan parent agar dua submit
            // bersamaan pada laporan yang sama berjalan serial (row lock InnoDB
            // yang efektif karena baris parent selalu ada). Tanpa ini,
            // existing=8 + submit=2 dari dua request bisa lolos cek di atas
            // dan menghasilkan total 12.
            Laporan::query()->where('id', $laporan->id)->lockForUpdate()->first();

            // Cek ulang batas akumulasi di dalam transaksi dengan lock pada
            // baris anak, sehingga hitungan tidak basi antar request.
            $existingTerkunci = LaporanSales::where('laporan_id', $laporan->id)->lockForUpdate()->count();
            if ($existingTerkunci + $count > 10) {
                DB::rollBack();
                return back()->withInput()->with('sales_error', 'Maksimal 10 data customer per laporan. Sudah ada ' . $existingTerkunci . ' data, Anda mencoba menambah ' . $count . ' lagi (total ' . ($existingTerkunci + $count) . ').');
            }

            foreach ($customers as $c) {
                LaporanSales::create([
                    'user_id'         => $user->id,
                    'laporan_id'      => $laporan->id,
                    'tanggal'         => $tanggal,
                    'nama_customer'   => trim($c['nama_customer']),
                    'no_telp'         => isset($c['no_telp']) ? (trim($c['no_telp']) !== '' ? trim($c['no_telp']) : null) : null,
                    'alamat_customer' => trim($c['alamat_customer']),
                    'provider'        => $c['provider'],
                    'kelebihan'       => isset($c['kelebihan']) ? (trim($c['kelebihan']) !== '' ? trim($c['kelebihan']) : null) : null,
                    'kekurangan'      => isset($c['kekurangan']) ? (trim($c['kekurangan']) !== '' ? trim($c['kekurangan']) : null) : null,
                ]);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan collect Sales', ['laporan_id' => $laporan->id, 'user_id' => $user->id, 'msg' => $e->getMessage()]);
            return back()->withInput()->with('sales_error', 'Gagal menyimpan data customer. Silakan coba lagi.');
        }

        return redirect()->route('laporan.sales.data')->with('sales_success', 'Berhasil menyimpan ' . $count . ' data customer untuk laporan #' . $laporan->id . '.');
    }
}
