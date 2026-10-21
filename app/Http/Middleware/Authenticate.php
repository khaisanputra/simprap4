<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    public function handle(Request $request, Closure $next)
    {
        // Sumber identitas tunggal: authenticated Laravel user (Auth).
        // Tidak ada fallback ke session user_id.
        $user = $request->user() ?? Auth::user();

        // Tidak ada sesi login -> arahkan ke halaman login.
        if (! $user) {
            return redirect()->route('login');
        }

        // Akun yang dinonaktifkan tidak boleh melanjutkan akses.
        if ($user->status !== 'Aktif') {
            return $this->invalidSession($request, 'Akun Anda berstatus Nonaktif.');
        }

        return $next($request);
    }

    /**
     * Bersihkan seluruh sesi dan arahkan kembali ke halaman login.
     */
    protected function invalidSession(Request $request, ?string $pesan = null)
    {
        Auth::logout();
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($pesan) {
            $request->session()->flash('error', $pesan);
        }

        return redirect()->route('login');
    }
}