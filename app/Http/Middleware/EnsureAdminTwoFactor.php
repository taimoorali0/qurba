<?php
// ===== QURBA: every admin must set up and pass 2FA before using /admin =====
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminTwoFactor
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if (! $u) return $next($request);
        if (! $u->two_factor_confirmed_at) return redirect()->route('admin.2fa.setup');
        if (! $request->session()->get('admin_2fa_passed')) {
            $request->session()->put('url.intended', $request->fullUrl());
            return redirect()->route('admin.2fa.challenge');
        }
        return $next($request);
    }
}
