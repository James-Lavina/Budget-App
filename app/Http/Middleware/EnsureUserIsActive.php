<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Suspension was announced to the admin ("they will be unable to log in")
 * but nothing enforced it. Registered in the 'web' group so it also covers
 * Livewire's AJAX requests, which don't pass through route-level middleware.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user && $user->status === 'suspended') {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->flash('auth_error', 'Your account has been suspended. Please contact an administrator.');

            // Livewire XHR: 419 makes Livewire offer a page refresh, which lands on /login.
            if ($request->hasHeader('X-Livewire')) {
                return response()->json(['message' => 'Session ended.'], 419);
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}