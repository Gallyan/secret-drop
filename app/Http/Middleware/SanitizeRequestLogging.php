<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to prevent sensitive data from leaking into logs.
 *
 * Zero-knowledge principle: URLs containing tokens or fragments
 * must never be logged in their complete form.
 *
 * Seul le server bag est réécrit : l'URI de la requête, le chemin, les paramètres
 * de route et les en-têtes gardent leurs vraies valeurs (le routage en dépend) ;
 * les logs sont donc protégés par App\Logging\SanitizeProcessor.
 */
class SanitizeRequestLogging
{
    /**
     * Route patterns containing sensitive tokens.
     */
    private const SENSITIVE_ROUTE_PATTERNS = [
        '#^/s/[^/]+#',           // /s/{token} - secret view
        '#^/api/secrets/[^/]+#', // /api/secrets/{token} - API endpoints
        '#^(?:/[a-z]{2})?/admin/verify/[^/]+#', // /{locale?}/admin/verify/{token} - magic links
        '#^(?:/[a-z]{2})?/superadmin/verify/[^/]+#', // /{locale?}/superadmin/verify/{token}
    ];

    private const SENSITIVE_QUERY_PARAMS = ['token', 'key', 'secret', 'admin_token'];

    public function handle(Request $request, Closure $next): Response
    {
        $this->sanitizeServerVars($request);

        return $next($request);
    }

    private function sanitizeServerVars(Request $request): void
    {
        $uri = $request->getRequestUri();
        $queryString = $request->server->getString('QUERY_STRING');
        $hasSensitiveQuery = $queryString !== '' && $this->containsSensitiveParams($queryString);

        $sanitizedUri = $this->sanitizeUri($uri);

        if ($hasSensitiveQuery) {
            $sanitizedUri = explode('?', $sanitizedUri, 2)[0].'?[REDACTED]';
            $request->server->set('QUERY_STRING', '[REDACTED]');
        }

        if ($uri !== $sanitizedUri) {
            $request->server->set('REQUEST_URI', $sanitizedUri);
            $request->server->set('ORIGINAL_REQUEST_URI', '[REDACTED]');
        }

        if ($request->server->has('HTTP_REFERER')) {
            $referer = $request->server->getString('HTTP_REFERER');
            $request->server->set('HTTP_REFERER', $this->sanitizeFullUrl($referer));
        }
    }

    private function sanitizeUri(string $uri): string
    {
        foreach (self::SENSITIVE_ROUTE_PATTERNS as $pattern) {
            if (preg_match($pattern, $uri)) {
                return preg_replace('#(/[^/]+)/[A-Za-z0-9_-]{20,}#', '$1/[TOKEN]', $uri) ?? $uri;
            }
        }

        return $uri;
    }

    /** Les motifs de route sont ancrés sur le chemin : ils s'appliquent au chemin de l'URL absolue. */
    private function sanitizeFullUrl(string $url): string
    {
        $url = preg_replace('/#.*$/s', '#[REDACTED]', $url) ?? $url;
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return $url;
        }

        $sanitizedPath = $this->sanitizeUri($path);
        $pathPosition = strpos($url, $path);

        if ($sanitizedPath === $path || $pathPosition === false) {
            return $url;
        }

        return substr_replace($url, $sanitizedPath, $pathPosition, strlen($path));
    }

    private function containsSensitiveParams(string $queryString): bool
    {
        foreach (self::SENSITIVE_QUERY_PARAMS as $param) {
            if (str_contains(strtolower($queryString), "{$param}=")) {
                return true;
            }
        }

        return false;
    }
}
