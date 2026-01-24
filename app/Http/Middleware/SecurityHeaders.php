<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $nonce = csp_nonce();
        $csp = $this->buildCsp($nonce);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }

    private function buildCsp(string $nonce): string
    {
        $isLocal = app()->environment('local');
        $connectSrc = $isLocal
            ? "connect-src 'self' ws://localhost:* http://localhost:*"
            : "connect-src 'self'";

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'",
            "style-src 'self' 'nonce-{$nonce}' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self'",
            $connectSrc,
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        return implode('; ', $directives);
    }
}
