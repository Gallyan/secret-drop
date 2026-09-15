<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocalizedPagesControllerTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function slugsOfAnotherLocale(): array
    {
        return [
            'english slug under french' => ['/fr/how-it-works', 'http://localhost/fr/comment-ca-marche'],
            'french slug under english' => ['/en/comment-ca-marche', 'http://localhost/en/how-it-works'],
            'spanish slug under french' => ['/fr/preguntas-frecuentes', 'http://localhost/fr/faq'],
            'german slug under japanese' => ['/ja/impressum', 'http://localhost/ja/legal-notice'],
            'portuguese slug under spanish' => ['/es/perguntas-frequentes', 'http://localhost/es/preguntas-frecuentes'],
            'slug shared by spanish and portuguese under italian' => ['/it/como-funciona', 'http://localhost/it/come-funziona'],
            'legal slug shared by spanish and portuguese under italian' => ['/it/aviso-legal', 'http://localhost/it/avviso-legale'],
        ];
    }

    /** Vérifie qu'un slug d'une autre locale redirige en 301 vers le slug traduit de la locale de l'URL. */
    #[DataProvider('slugsOfAnotherLocale')]
    public function testSlugOfAnotherLocaleRedirectsPermanentlyToTheTranslatedSlug(string $uri, string $expectedLocation): void
    {
        $response = $this->get($uri);

        $response->assertMovedPermanently();
        $response->assertRedirect($expectedLocation);
    }

    /** Vérifie qu'un slug inconnu de toutes les locales retourne 404. */
    public function testUnknownSlugReturnsNotFound(): void
    {
        $response = $this->get('/en/this-page-does-not-exist');

        $response->assertNotFound();
    }
}
