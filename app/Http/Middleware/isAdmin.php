<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class isAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is logged in and has the 'admin' or 'super_admin' role.
        // super_admin must pass this same gate to keep access to every
        // existing admin page (Dashboard, User Management, etc.) — the
        // stricter 'super_admin' middleware only adds an EXTRA gate on
        // top of this one for the admin-accounts page specifically.
        if(auth()->check() && in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return $next($request);
        }

        // if not admin, redirect them to student dashboard
        return redirect()->route('student.dashboard')->with('error', 'Unauthorized access attempt');
    }
}