<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->role !== 'super_admin') {
            abort(403, 'This page is restricted to super administrators.');
        }

        return $next($request);
    }
}