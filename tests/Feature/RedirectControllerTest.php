<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RedirectControllerTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function rootNegotiations(): array
    {
        return [
            'french header' => ['fr', 'http://localhost/fr'],
            'english header' => ['en', 'http://localhost/en'],
            'empty header defaults to french' => ['', 'http://localhost/fr'],
        ];
    }

    /** Vérifie que la racine redirige en 302 vers l'accueil de la locale négociée, sans slash final, avec Vary: Accept-Language. */
    #[DataProvider('rootNegotiations')]
    public function testRootRedirectsToNegotiatedHome(string $acceptLanguage, string $expectedLocation): void
    {
        $response = $this->withHeader('Accept-Language', $acceptLanguage)->get('/');

        $response->assertFound();
        $response->assertHeader('Location', $expectedLocation);
        $response->assertHeader('Vary', 'Accept-Language');
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function legacyPages(): array
    {
        return [
            'how-it-works in french' => ['/how-it-works', 'fr', 'http://localhost/fr/comment-ca-marche'],
            'use-cases in english' => ['/use-cases', 'en', 'http://localhost/en/use-cases'],
            'legal in french' => ['/legal', 'fr', 'http://localhost/fr/mentions-legales'],
            'faq in spanish' => ['/faq', 'es', 'http://localhost/es/preguntas-frecuentes'],
        ];
    }

    /** Vérifie qu'une ancienne URL non localisée redirige en 301 vers le slug traduit de la locale négociée, avec Vary: Accept-Language. */
    #[DataProvider('legacyPages')]
    public function testLegacyPageRedirectsPermanentlyToNegotiatedLocalizedPage(string $uri, string $acceptLanguage, string $expectedLocation): void
    {
        $response = $this->withHeader('Accept-Language', $acceptLanguage)->get($uri);

        $response->assertMovedPermanently();
        $response->assertRedirect($expectedLocation);
        $response->assertHeader('Vary', 'Accept-Language');
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function nonLocalizedBackOffices(): array
    {
        return [
            'admin in french' => ['/admin', 'fr', 'http://localhost/fr/admin'],
            'superadmin in english' => ['/superadmin', 'en', 'http://localhost/en/superadmin'],
        ];
    }

    /** Vérifie que /admin et /superadmin redirigent en 302 vers leur version dans la locale négociée, avec Vary: Accept-Language. */
    #[DataProvider('nonLocalizedBackOffices')]
    public function testNonLocalizedBackOfficeRedirectsToNegotiatedLocale(string $uri, string $acceptLanguage, string $expectedLocation): void
    {
        $response = $this->withHeader('Accept-Language', $acceptLanguage)->get($uri);

        $response->assertFound();
        $response->assertRedirect($expectedLocation);
        $response->assertHeader('Vary', 'Accept-Language');
    }
}
