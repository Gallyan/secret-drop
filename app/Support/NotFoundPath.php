<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/**
 * Chemin d'une 404 réduit à un gabarit stockable : jamais de token, d'identifiant ni de query string.
 *
 * Le chemin réel est conservé pour les segments statiques (c'est ce qu'on cherche : /wp-login.php,
 * /fr/page-inconnue), mais les valeurs des paramètres de route et les séquences qui ressemblent à
 * un token, à un identifiant ou à une adresse e-mail sont remplacés par un marqueur.
 */
final class NotFoundPath
{
    public const MAX_LENGTH = 150;

    private const TOKEN_MIN_LENGTH = 16;

    /** Paramètres dont la valeur reste lisible : ils décrivent la page demandée, pas une donnée sensible. */
    private const READABLE_PARAMETERS = ['locale', 'pageSlug'];

    public static function template(Request $request): string
    {
        $parameterNames = self::sensitiveParameterNames($request->route());
        $segments = [];

        foreach (explode('/', $request->path()) as $segment) {
            $segments[] = self::maskSegment($segment, $parameterNames);
        }

        $path = mb_scrub('/'.implode('/', $segments));

        return mb_substr($path, 0, self::MAX_LENGTH);
    }

    /**
     * @return array<string, string> valeur décodée du paramètre => nom du paramètre
     */
    private static function sensitiveParameterNames(mixed $route): array
    {
        if (! $route instanceof Route) {
            return [];
        }

        $names = [];

        foreach ($route->parameters() as $name => $value) {
            if (! is_string($value) || $value === '' || in_array($name, self::READABLE_PARAMETERS, true)) {
                continue;
            }

            $names[$value] = $name;
        }

        return $names;
    }

    /**
     * @param  array<string, string>  $parameterNames
     */
    private static function maskSegment(string $segment, array $parameterNames): string
    {
        $decoded = rawurldecode($segment);

        if (isset($parameterNames[$decoded])) {
            return "{{$parameterNames[$decoded]}}";
        }

        if (str_contains($decoded, '@')) {
            return '{email}';
        }

        if (ctype_digit($segment)) {
            return '{id}';
        }

        return preg_replace_callback(
            '/[A-Za-z0-9_-]{'.self::TOKEN_MIN_LENGTH.',}/',
            fn (array $match): string => self::looksLikeToken($match[0]) ? '{token}' : $match[0],
            $segment
        ) ?? '{token}';
    }

    /** Une longue séquence mêlant chiffres ou casse, qu'un slug lisible ne contient pas. */
    private static function looksLikeToken(string $segment): bool
    {
        if (strlen($segment) < self::TOKEN_MIN_LENGTH) {
            return false;
        }

        $hasDigit = preg_match('/\d/', $segment) === 1;
        $mixedCase = preg_match('/[a-z]/', $segment) === 1 && preg_match('/[A-Z]/', $segment) === 1;

        return $hasDigit || $mixedCase;
    }
}
