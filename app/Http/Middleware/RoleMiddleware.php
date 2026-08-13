<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('redirect', $request->fullUrl());
        }

        if (! in_array(auth()->user()->role, $roles, true)) {
            abort(403, 'Acceso no autorizado.');
        }

        return $next($request);
    }
}
