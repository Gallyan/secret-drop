<?php

namespace Tests\Feature;

use Tests\TestCase;

class GeoControllerTest extends TestCase
{
    public function testLlmsTxtReturnsCorrectFormatAndContent(): void
    {
        $response = $this->get('/llms.txt');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');

        $content = $response->getContent();

        $this->assertStringStartsWith('# Secret Drop', $content);
        $this->assertStringContains('> Free, open-source zero-knowledge secret sharing app', $content);
        $this->assertStringContains('## Docs', $content);
        $this->assertStringContains('## Key Facts', $content);
        $this->assertStringContains('## Contact', $content);
        $this->assertStringContains('[Homepage]', $content);
        $this->assertStringContains('[How It Works]', $content);
        $this->assertStringContains('[Use Cases]', $content);
        $this->assertStringContains('[FAQ]', $content);
        $this->assertStringContains('[Legal Notice]', $content);
        $this->assertStringContains('[Source Code]', $content);
        $this->assertStringContains('/en)', $content);
        $this->assertStringContains('/en/how-it-works)', $content);
    }

    public function testLlmsFullTxtReturnsCorrectFormatAndContent(): void
    {
        $response = $this->get('/llms-full.txt');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');

        $content = $response->getContent();

        $this->assertStringStartsWith('# Secret Drop -- Full Documentation', $content);
        $this->assertStringContains('## Docs', $content);
        $this->assertStringContains('## Localized Versions', $content);
        $this->assertStringContains('## Security Architecture', $content);
        $this->assertStringContains('## Key Facts', $content);
        $this->assertStringContains('## Contact', $content);
        $this->assertStringContains('[Homepage]', $content);
        $this->assertStringContains('/en)', $content);
        $this->assertStringContains('/fr)', $content);
    }

    public function testLlmsTxtHasNoUnreplacedPlaceholders(): void
    {
        $response = $this->get('/llms.txt');
        $content = $response->getContent();

        $this->assertStringNotContains('BASE_URL', $content);
        $this->assertStringNotContains('SITE_URL', $content);
        $this->assertStringNotContains('GITHUB_URL', $content);
        $this->assertStringNotContains('EDITOR_NAME', $content);
    }

    public function testLlmsFullTxtHasNoUnreplacedPlaceholders(): void
    {
        $response = $this->get('/llms-full.txt');
        $content = $response->getContent();

        $this->assertStringNotContains('BASE_URL', $content);
        $this->assertStringNotContains('SITE_URL', $content);
        $this->assertStringNotContains('GITHUB_URL', $content);
        $this->assertStringNotContains('WEBSITE_URL', $content);
        $this->assertStringNotContains('CONTACT_URL', $content);
        $this->assertStringNotContains('EDITOR_NAME', $content);
    }

    /** Sans fichier signé, security.txt est généré dynamiquement. */
    public function testSecurityTxtFallsBackToDynamicContentWhenNoSignedFile(): void
    {
        $this->assertFileDoesNotExist(storage_path('app/security.txt.asc'));

        $response = $this->get('/.well-known/security.txt');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $content = $response->getContent();

        $this->assertStringContains('Contact: mailto:', $content);
        $this->assertStringContains('Expires: ', $content);
        $this->assertStringContains('Preferred-Languages: fr, en', $content);
        $this->assertStringContains('Canonical: ', $content);
    }

    /** Le fichier signé storage/app/security.txt.asc est servi tel quel s'il existe. */
    public function testSecurityTxtServesSignedFileWhenPresent(): void
    {
        $signedPath = storage_path('app/security.txt.asc');
        $signedContent = "-----BEGIN PGP SIGNED MESSAGE-----\nHash: SHA256\n\nContact: mailto:test@example.com\n-----BEGIN PGP SIGNATURE-----\nfake\n-----END PGP SIGNATURE-----\n";

        file_put_contents($signedPath, $signedContent);

        try {
            $response = $this->get('/.well-known/security.txt');

            $response->assertStatus(200);
            $this->assertEquals($signedContent, $response->getContent());
        } finally {
            unlink($signedPath);
        }
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that string contains '{$needle}'.",
        );
    }

    private function assertStringNotContains(string $needle, string $haystack): void
    {
        $this->assertFalse(
            str_contains($haystack, $needle),
            "Failed asserting that string does not contain '{$needle}'.",
        );
    }
}
