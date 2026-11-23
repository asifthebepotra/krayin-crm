<?php

namespace Webkul\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveKrayinUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('user');
        $user = $guard->user();

        if (! $user || ! (bool) $user->status) {
            if ($user) {
                $guard->logout();
            }

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->route('admin.session.create');
        }

        return $next($request);
    }
}
