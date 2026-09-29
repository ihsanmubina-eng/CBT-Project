<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login')->with('error', 'Masuk dengan akun staf untuk membuka panel.');
        }

        return $next($request);
    }
}