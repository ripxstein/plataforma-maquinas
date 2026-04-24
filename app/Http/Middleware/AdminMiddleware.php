<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Si no está logueado o no es admin → bloquea
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            return redirect('/dashboard'); // acceso prohibido
        }

        return $next($request);
    }
}
