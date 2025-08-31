<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTeacherRole
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['Teacher','Admin'])) {
            abort(403, 'Access denied. Teachers only.');
        }
        return $next($request);
    }
}
