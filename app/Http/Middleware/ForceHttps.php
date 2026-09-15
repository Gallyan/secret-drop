<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/** Redirects HTTP requests to HTTPS and forces the URL scheme in production. */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('production')) {
            return $next($request);
        }

        URL::forceScheme('https');

        if ($request->secure()) {
            return $next($request);
        }

        return redirect()->secure($request->getRequestUri(), $this->redirectStatus($request));
    }

    /** Un 301 laisse le navigateur transformer une écriture en GET et perdre le corps ; un 308 conserve les deux. */
    private function redirectStatus(Request $request): int
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return Response::HTTP_MOVED_PERMANENTLY;
        }

        return Response::HTTP_PERMANENTLY_REDIRECT;
    }
}
