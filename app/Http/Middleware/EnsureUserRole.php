<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * Menerima parameter role yang diperbolehkan, contoh:
     * ->middleware('role:admin')
     * ->middleware('role:admin,siswa')
     *
     * Sumber role: authenticated Laravel user (Auth) saja.
     * Tidak ada fallback ke session user_id/session role.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user() ?? Auth::user();
        $role = $user?->role ?? null;

        if (! in_array($role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
