<?php

namespace Webkul\StatamicIntegration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureKrayinCmsUserIsActive
{
    /**
     * Statamic uses its own CP middleware group. Apply Krayin's employee
     * status rule there as well, without applying the CRM route-name Bouncer
     * to every Statamic route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('user');
        $user = $guard->user();

        if (! $user || ! (bool) $user->status) {
            $guard->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect()->route('admin.session.create');
        }

        return $next($request);
    }
}