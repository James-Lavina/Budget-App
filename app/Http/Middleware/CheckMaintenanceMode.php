<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        $settings = AppSetting::current();

        if (!$settings->maintenance_mode_enabled) {
            return $next($request);
        }

        $user = auth()->user();

        if ($user && in_array($user->role, ['admin', 'super_admin'])) {
            return $next($request);
        }

        // Always let logout through so a locked-out student can still sign out.
        if ($request->routeIs('logout') || $request->routeIs('maintenance')) {
            return $next($request);
        }

        return response()->view('maintenance', [
            'appSettings' => $settings,
        ], 503);
    }
}