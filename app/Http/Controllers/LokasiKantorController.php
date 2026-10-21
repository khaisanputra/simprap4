<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLokasiKantorRequest;
use App\Support\LokasiKantor;
use App\Support\WaktuAbsen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LokasiKantorController extends Controller
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

    /**
     * Halaman Pengaturan Lokasi Kantor (khusus admin).
     */
    public function index(Request $request)
    {
        $userSession = $this->userSession($request);
        $lokasi      = LokasiKantor::all();
        $jamAbsen    = WaktuAbsen::all('masuk');
        $jamPulang   = WaktuAbsen::all('pulang');
        $jamLaporan  = WaktuAbsen::all('laporan');

        return view('lokasi-kantor.index', compact('userSession', 'lokasi', 'jamAbsen', 'jamPulang', 'jamLaporan'));
    }

    /**
     * Simpan perubahan Pengaturan Lokasi Kantor ke database.
     * Authorization dijamin oleh middleware role:admin & authorize() request.
     */
    public function update(StoreLokasiKantorRequest $request)
    {
        LokasiKantor::set('lokasi_kantor_nama', $request->nama_kantor);
        LokasiKantor::set('lokasi_kantor_alamat', $request->alamat_kantor);
        LokasiKantor::set('lokasi_kantor_latitude', $request->latitude);
        LokasiKantor::set('lokasi_kantor_longitude', $request->longitude);
        LokasiKantor::set('lokasi_kantor_radius', $request->radius_kantor);
        WaktuAbsen::set('jam_absen_mulai', $request->jam_mulai);
        WaktuAbsen::set('jam_absen_selesai', $request->jam_selesai);
        WaktuAbsen::set('jam_pulang_mulai', $request->jam_pulang_mulai);
        WaktuAbsen::set('jam_pulang_selesai', $request->jam_pulang_selesai);
        // Jam Laporan (Sales & Teknisi) 08:00-23:59 default
        if ($request->filled('jam_laporan_mulai')) {
            WaktuAbsen::set('jam_laporan_mulai', $request->jam_laporan_mulai);
        }
        if ($request->filled('jam_laporan_selesai')) {
            WaktuAbsen::set('jam_laporan_selesai', $request->jam_laporan_selesai);
        }

        $byUser = $request->user() ?? \Illuminate\Support\Facades\Auth::user();
        Log::info('Admin memperbarui pengaturan lokasi kantor & jam absen', [
            'by'       => $byUser?->username ?? $byUser?->nama ?? 'system',
            'nama'     => $request->nama_kantor,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius'   => $request->radius_kantor,
            'jam_masuk' => $request->jam_mulai.' - '.$request->jam_selesai,
            'jam_pulang' => $request->jam_pulang_mulai.' - '.$request->jam_pulang_selesai,
            'jam_laporan' => ($request->jam_laporan_mulai ?? WaktuAbsen::mulai('laporan')).' - '.($request->jam_laporan_selesai ?? WaktuAbsen::selesai('laporan')),
        ]);

        return redirect()->route('lokasi-kantor.index')
            ->with('success', 'Pengaturan Lokasi Kantor berhasil disimpan.');
    }
}