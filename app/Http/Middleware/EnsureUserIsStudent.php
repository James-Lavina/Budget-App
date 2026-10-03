<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsStudent
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && in_array(auth()->user()->role, ['admin', 'super_admin'], true)) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}