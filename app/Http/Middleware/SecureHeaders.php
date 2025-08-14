<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $res = $next($request);
        $res->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $res->headers->set('X-Content-Type-Options', 'nosniff');
        $res->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $res->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        $dev = app()->isLocal();
        $vite = "http://localhost:5173 ws://localhost:5173";
        $csp = $dev
            ? "default-src 'self'; script-src 'self' {$vite}; style-src 'self' 'unsafe-inline' {$vite}; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self' {$vite} wss:;"
            : "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self' wss:;";
        $res->headers->set('Content-Security-Policy', $csp);

        return $res;
    }
}
