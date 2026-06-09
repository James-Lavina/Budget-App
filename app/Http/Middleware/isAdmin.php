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
        // Check if user is logged in and has the 'admin' role
        if(auth()->check() && auth()->user()->role === 'admin') {
            return $next($request);
        }

        // if not admin, redirect them to student dashboard
        return redirect()->route('student.dashboard')->with('error', 'Unauthorized access attempt');
    }
}
