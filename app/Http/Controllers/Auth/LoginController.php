<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            $request->session()->flash('error', 'Username atau Password salah.');

            return back()->withErrors([
                'message' => 'Username atau Password salah.',
            ])->withInput();
        }

        // Akun berstatus Nonaktif tidak boleh masuk sistem.
        if ($user->status === 'Nonaktif') {
            $request->session()->flash('error', 'Akun Anda berstatus Nonaktif.');

            return back()->withErrors([
                'message' => 'Akun Anda berstatus Nonaktif.',
            ])->withInput();
        }

        Auth::login($user);
        $request->session()->regenerate();
        // Legacy session puts removed (Tahap 4.2): user_id/role tidak lagi dipakai untuk authorization.
        // Laravel Auth (Auth::user()/Auth::id()/$request->user()) adalah satu-satunya sumber identitas.

        Log::info('Pengguna berhasil login', [
            'user_id' => $user->id,
            'username' => $user->username,
        ]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->forget(['user_id', 'username', 'role']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
