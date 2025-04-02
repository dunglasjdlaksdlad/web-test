<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
  public function handle(Request $request, Closure $next)
    {
    //    dd(Auth::user()->is_active);
        if (Auth::check() && Auth::user()->is_active === false) {
            Auth::logout();
             $request->session()->invalidate();
            $request->session()->regenerateToken();
          return redirect()->route('login')->withErrors(['email' => 'Tài khoản của bạn đã bị vô hiệu hóa.']);
        }

        return $next($request);
    }
}
