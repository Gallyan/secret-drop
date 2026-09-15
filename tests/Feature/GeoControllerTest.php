<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GeoControllerTest extends TestCase
{
    /** Vérifie que llms.txt suit le format llmstxt.org avec les liens absolus des pages anglaises. */
    public function testLlmsTxtReturnsCorrectFormatAndContent(): void
    {
        $response = $this->get('/llms.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $content = $response->getContent();
        $this->assertStringStartsWith("# Secret Drop\n\n> Free, open-source zero-knowledge secret sharing app", $content);
        $this->assertStringContainsString('For full documentation, see: http://localhost/llms-full.txt', $content);
        $this->assertStringContainsString("## Docs\n", $content);
        $this->assertStringContainsString("## Key Facts\n", $content);
        $this->assertStringContainsString("## Contact\n", $content);
        $this->assertStringContainsString('- [Homepage](http://localhost/en):', $content);
        $this->assertStringContainsString('- [How It Works](http://localhost/en/how-it-works):', $content);
        $this->assertStringContainsString('- [Use Cases](http://localhost/en/use-cases):', $content);
        $this->assertStringContainsString('- [FAQ](http://localhost/en/faq):', $content);
        $this->assertStringContainsString('- [Legal Notice](http://localhost/en/legal-notice):', $content);
        $this->assertStringContainsString('- Contact form: http://localhost/contact', $content);
    }

    /** Vérifie que llms-full.txt contient ses sections et des liens absolus exacts vers les pages et les versions localisées. */
    public function testLlmsFullTxtReturnsCorrectFormatAndContent(): void
    {
        $response = $this->get('/llms-full.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $content = $response->getContent();
        $this->assertStringStartsWith('# Secret Drop -- Full Documentation', $content);
        $this->assertStringContainsString('## Docs', $content);
        $this->assertStringContainsString('## Localized Versions', $content);
        $this->assertStringContainsString('## Security Architecture', $content);
        $this->assertStringContainsString('## Key Facts', $content);
        $this->assertStringContainsString('## Contact', $content);
        $this->assertStringContainsString("\n- [Homepage](http://localhost/en): Main entry point", $content);
        $this->assertStringContainsString("\n- [How It Works](http://localhost/en/how-it-works): Detailed technical walkthrough", $content);
        $this->assertStringContainsString("\n- [English](http://localhost/en): /en/\n", $content);
        $this->assertStringContainsString("\n- [French](http://localhost/fr): /fr/\n", $content);
        $this->assertStringContainsString("\n- [Arabic](http://localhost/ar): /ar/\n", $content);
        $this->assertStringContainsString("\n- Contact form: http://localhost/contact\n", $content);
    }

    /** Vérifie que llms-full.txt ne laisse aucun marqueur de substitution. */
    public function testLlmsFullTxtHasNoUnreplacedPlaceholders(): void
    {
        $content = $this->get('/llms-full.txt')->getContent();

        $this->assertDoesNotMatchRegularExpression('/\b(BASE_URL|SITE_URL|GITHUB_URL|WEBSITE_URL|CONTACT_URL|EDITOR_NAME)\b/', $content);
    }

    /**
     * Vérifie que les fichiers llms gardent des liens valides sans configuration sociale.
     *
     * Une installation neuve laisse SOCIAL_* commentés dans .env.example, et config() renvoie
     * null pour une clé existante valant null : un défaut passé en second argument ne s'applique jamais.
     */
    public function testLlmsFilesKeepValidLinksWithoutSocialConfig(): void
    {
        config(['legal.social.github' => null, 'legal.social.website' => null]);

        foreach (['/llms.txt', '/llms-full.txt'] as $uri) {
            $content = $this->get($uri)->assertOk()->getContent();

            $this->assertStringNotContainsString('[Source Code]()', $content);
            $this->assertStringNotContainsString("Source code:\n", $content);
            $this->assertMatchesRegularExpression('#\[Source Code\]\(https://\S+\)#', $content);
        }
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function emptyWebsiteConfigs(): array
    {
        return [
            'null website' => [null],
            'empty website' => [''],
        ];
    }

    /** Vérifie que llms.txt retombe sur le site du créateur par défaut quand l'URL website n'est pas configurée. */
    #[DataProvider('emptyWebsiteConfigs')]
    public function testLlmsTxtFallsBackToDefaultWebsiteWhenConfigIsEmpty(?string $website): void
    {
        config(['legal.social.website' => $website]);

        $content = $this->get('/llms.txt')->getContent();

        $this->assertStringContainsString("- Creator website: https://www.orsal.fr\n", $content);
    }

    /** Vérifie que llms.txt utilise l'URL website configurée. */
    public function testLlmsTxtUsesConfiguredWebsite(): void
    {
        config(['legal.social.website' => 'https://creator.example.test']);

        $content = $this->get('/llms.txt')->getContent();

        $this->assertStringContainsString("- Creator website: https://creator.example.test\n", $content);
    }

    /** Vérifie que, sans fichier signé, security.txt est généré avec l'email de contact et une expiration à un an. */
    public function testSecurityTxtFallsBackToDynamicContentWhenNoSignedFile(): void
    {
        $this->useTemporaryStoragePath();
        $this->travelTo('2026-09-15T10:20:30Z');
        config(['legal.contact_email' => 'security@example.test']);

        $response = $this->get('/.well-known/security.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            "Contact: mailto:security@example.test\n"
            ."Expires: 2027-09-15T10:20:30Z\n"
            ."Preferred-Languages: fr, en\n"
            .'Canonical: http://localhost/.well-known/security.txt',
            $response->getContent(),
        );
    }

    /** Vérifie que security.txt dynamique retombe sur l'adresse mail.from sans email de contact configuré. */
    public function testSecurityTxtFallsBackToMailFromAddressWithoutContactEmail(): void
    {
        $this->useTemporaryStoragePath();
        config(['legal.contact_email' => null, 'mail.from.address' => 'fallback@example.test']);

        $response = $this->get('/.well-known/security.txt');

        $this->assertStringStartsWith("Contact: mailto:fallback@example.test\n", $response->getContent());
    }

    /** Vérifie que le fichier signé storage/app/security.txt.asc est servi tel quel, en texte brut, s'il existe. */
    public function testSecurityTxtServesSignedFileWhenPresent(): void
    {
        $storagePath = $this->useTemporaryStoragePath();
        $signedContent = "-----BEGIN PGP SIGNED MESSAGE-----\nHash: SHA256\n\nContact: mailto:test@example.com\n-----BEGIN PGP SIGNATURE-----\nfake\n-----END PGP SIGNATURE-----\n";
        File::put("{$storagePath}/app/security.txt.asc", $signedContent);

        $response = $this->get('/.well-known/security.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame($signedContent, $response->getContent());
    }

    /** Points storage_path() to a throwaway directory, removed when the application is torn down. */
    private function useTemporaryStoragePath(): string
    {
        $storagePath = sys_get_temp_dir().'/secret-drop-geo-test-'.Str::random(16);
        File::ensureDirectoryExists("{$storagePath}/app");
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($storagePath));
        $this->app->useStoragePath($storagePath);

        return $storagePath;
    }
}
