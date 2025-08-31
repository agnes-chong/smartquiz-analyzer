<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdminRole
{
    public function handle(Request $request, Closure $next)
    {
        // If using a 'role' column in users table
        if (!auth()->check() || auth()->user()->role !== 'Admin') {
            abort(403, 'Access denied. Admins only.');
        }

        return $next($request);
    }
}
