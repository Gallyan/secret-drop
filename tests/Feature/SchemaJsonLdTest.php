<?php

namespace Tests\Feature;

use App\Support\LocaleConfig;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SchemaJsonLdTest extends TestCase
{
    private const HTML_ENTITY_PATTERN = '/&(?:#\d+|#x[\da-f]+|[a-z][a-z\d]*);/i';

    /**
     * @return array<string, array{string}>
     */
    public static function locales(): array
    {
        return collect(LocaleConfig::SUPPORTED_LOCALES)
            ->mapWithKeys(fn (string $locale): array => [$locale => [$locale]])
            ->all();
    }

    /**
     * @return array<string, array{string, string, int}>
     */
    public static function innerPagesOfEveryLocale(): array
    {
        $blockCounts = ['how-it-works' => 3, 'use-cases' => 2, 'legal' => 1, 'faq' => 3];
        $cases = [];

        foreach (LocaleConfig::SUPPORTED_LOCALES as $locale) {
            foreach ($blockCounts as $page => $count) {
                $cases["{$locale} {$page}"] = [$locale, $page, $count];
            }
        }

        return $cases;
    }

    /** Vérifie que le bloc @graph de l'accueil est du JSON valide, sans entité HTML et porteur du nonce CSP, dans chaque locale. */
    #[DataProvider('locales')]
    public function testHomepageJsonLdIsValidJsonWithoutHtmlEntities(string $locale): void
    {
        $response = $this->get("/{$locale}");

        $this->assertJsonLdBlocksAreClean($response, 1);
    }

    /** Vérifie que chaque bloc JSON-LD d'une page interne est du JSON valide, sans entité HTML et porteur du nonce CSP, dans chaque locale. */
    #[DataProvider('innerPagesOfEveryLocale')]
    public function testInnerPageJsonLdIsValidJsonWithoutHtmlEntities(string $locale, string $page, int $expectedBlocks): void
    {
        $response = $this->get(localized_route($page, $locale));

        $this->assertJsonLdBlocksAreClean($response, $expectedBlocks);
    }

    /** Vérifie que les réponses FAQ françaises gardent la vraie apostrophe au lieu d'une entité HTML. */
    public function testFrenchFaqAnswerKeepsTheRealApostrophe(): void
    {
        $faq = $this->findByType($this->extractSchemas('/fr/faq'), 'FAQPage');

        $this->assertSame(
            'Oui, entièrement. Pas de compte, pas d\'abonnement, pas de frais cachés.',
            $faq->mainEntity[0]->acceptedAnswer->text,
        );
    }

    /** Vérifie que la réponse FAQ 12 française injecte la durée de validité configurée du magic link et le libellé du lien de gestion. */
    public function testFrenchFaqAnswerInjectsTheConfiguredMagicLinkLifetime(): void
    {
        config(['secrets.magic_link_ttl' => 17]);

        $faq = $this->findByType($this->extractSchemas('/fr/faq'), 'FAQPage');

        $this->assertSame(
            'Si vous avez fourni votre email, vous pouvez révoquer ou prolonger vos secrets via le lien « Gérer mes secrets » en bas de chaque page. '
            .'Vous recevrez un lien à usage unique valable 17 minutes — un magic link, sans mot de passe. Rien à voler, rien à pirater.',
            $faq->mainEntity[11]->acceptedAnswer->text,
        );
    }

    /** Vérifie qu'une traduction contenant guillemet, antislash, saut de ligne et balise script reste du JSON valide sans fermer le script. */
    public function testTranslationWithJsonAndHtmlMetacharactersCannotBreakTheJsonLd(): void
    {
        $tricky = "Quote \" backslash \\ newline\n</script><script>alert(1)</script>";
        __('messages.faq_q1', [], 'en');
        app('translator')->addLines(['messages.faq_q1' => $tricky], 'en');

        $response = $this->get('/en/faq');

        $this->assertStringNotContainsString('</script><script>alert(1)', $response->getContent());
        $faq = $this->findByType($this->schemasFromResponse($response), 'FAQPage');
        $this->assertSame($tricky, $faq->mainEntity[0]->name);
    }

    /** Vérifie que les réponses FAQ du JSON-LD ne contiennent ni balise HTML ni paramètre de traduction non remplacé, dans chaque locale. */
    #[DataProvider('locales')]
    public function testFaqAnswersContainNoHtmlNorPlaceholder(string $locale): void
    {
        $faq = $this->findByType($this->extractSchemas(localized_route('faq', $locale)), 'FAQPage');

        $this->assertCount(12, $faq->mainEntity);

        foreach ($faq->mainEntity as $question) {
            $text = $question->acceptedAnswer->text;

            $this->assertStringNotContainsString('<', $text, "Balise HTML dans la réponse : {$question->name}");
            $this->assertDoesNotMatchRegularExpression('/:[a-z_]+\b/', $text, "Paramètre non remplacé : {$question->name}");
        }
    }

    /** Vérifie que l'accueil expose un unique @graph avec tous les types attendus. */
    public function testHomepageHasSingleGraphWithAllSchemaTypes(): void
    {
        $types = collect($this->extractGraph('/en'))
            ->flatMap(function (object $schema): array {
                $type = $schema->{'@type'};

                return is_array($type) ? $type : [$type];
            })
            ->all();

        $this->assertSame(['WebSite', 'Organization', 'Person', 'WebApplication', 'SoftwareApplication'], $types);
    }

    /** Vérifie que l'entité WebApplication porte les métadonnées riches attendues. */
    public function testWebApplicationHasRichMetadata(): void
    {
        $app = collect($this->extractGraph('/en'))->first(
            fn (object $schema): bool => $schema->{'@type'} === ['WebApplication', 'SoftwareApplication']
        );

        $this->assertNotNull($app, 'Le graphe doit contenir une entité WebApplication');
        $this->assertSame('https://www.gnu.org/licenses/agpl-3.0', $app->license);
        $this->assertSame('2026-01-15', $app->dateCreated);
        $this->assertSame('2026-03-01', $app->datePublished);
        $this->assertSame('http://localhost/#person', $app->creator->{'@id'});
        $this->assertSame('http://localhost/#organization', $app->publisher->{'@id'});
        $this->assertCount(3, $app->screenshot);
        $this->assertSame('CreateAction', $app->potentialAction->{'@type'});
        $this->assertSame(['en', 'fr', 'de', 'es', 'it', 'pt', 'nl', 'pl', 'ja', 'ko', 'ar'], $app->inLanguage);
    }

    /** Vérifie que chaque entité du @graph de l'accueil garde son @id. */
    public function testHomepageSchemasKeepTheirIds(): void
    {
        $ids = collect($this->extractGraph('/en'))->map(fn (object $item): string => $item->{'@id'})->all();

        $this->assertSame([
            'http://localhost/#website',
            'http://localhost/#organization',
            'http://localhost/#person',
            'http://localhost/#application',
        ], $ids);
    }

    /** Vérifie que l'Organization a une date de fondation et un point de contact quand un email est configuré. */
    public function testOrganizationHasFoundingDateAndContactPoint(): void
    {
        config(['legal.contact_email' => 'contact@example.test']);

        $org = collect($this->extractGraph('/en'))->first(fn (object $schema): bool => $schema->{'@type'} === 'Organization');

        $this->assertSame('2026', $org->foundingDate);
        $this->assertSame('contact@example.test', $org->contactPoint->email);
        $this->assertSame('http://localhost/contact', $org->contactPoint->url);
    }

    /** Vérifie que l'Organization omet email et contactPoint quand aucun email n'est configuré. */
    public function testOrganizationOmitsContactWithoutConfiguredEmail(): void
    {
        config(['legal.contact_email' => null]);

        $org = collect($this->extractGraph('/en'))->first(fn (object $schema): bool => $schema->{'@type'} === 'Organization');

        $this->assertObjectNotHasProperty('email', $org);
        $this->assertObjectNotHasProperty('contactPoint', $org);
    }

    /** Vérifie que l'Organization et la Person partagent le même sameAs, limité aux profils sociaux renseignés, dans l'ordre de la configuration. */
    public function testOrganizationAndPersonShareConfiguredSocialProfilesAsSameAs(): void
    {
        config(['legal.social' => [
            'github' => 'https://github.example.test/secret-drop',
            'twitter' => '',
            'linkedin' => 'https://linkedin.example.test/in/creator',
            'website' => 'https://creator.example.test',
        ]]);

        $graph = collect($this->extractGraph('/en'));

        $expected = [
            'https://github.example.test/secret-drop',
            'https://linkedin.example.test/in/creator',
            'https://creator.example.test',
        ];
        $this->assertSame($expected, $graph->firstWhere('@type', 'Organization')->sameAs);
        $this->assertSame($expected, $graph->firstWhere('@type', 'Person')->sameAs);
    }

    /** Vérifie que l'Organization et la Person omettent sameAs quand aucun profil social n'est renseigné. */
    public function testOrganizationAndPersonOmitSameAsWithoutSocialProfiles(): void
    {
        config(['legal.social' => ['github' => '', 'twitter' => null, 'linkedin' => '', 'website' => '']]);

        $graph = collect($this->extractGraph('/en'));

        $this->assertObjectNotHasProperty('sameAs', $graph->firstWhere('@type', 'Organization'));
        $this->assertObjectNotHasProperty('sameAs', $graph->firstWhere('@type', 'Person'));
    }

    /** Vérifie que la Person référence l'organisation qui l'emploie et retombe sur le site par défaut sans URL website configurée. */
    public function testPersonHasWorksForAndDefaultWebsite(): void
    {
        config(['legal.social.website' => '']);

        $person = collect($this->extractGraph('/en'))->first(fn (object $schema): bool => $schema->{'@type'} === 'Person');

        $this->assertSame('http://localhost/#organization', $person->worksFor->{'@id'});
        $this->assertSame('https://www.orsal.fr', $person->url);
    }

    /** Vérifie que chaque page interne anglaise expose un BreadcrumbList accueil > page. */
    public function testInnerPagesHaveBreadcrumbList(): void
    {
        $pages = [
            '/en/how-it-works' => 'How it works',
            '/en/use-cases' => 'Use cases',
            '/en/faq' => 'Frequently asked questions',
            '/en/legal-notice' => 'Legal Notice',
        ];

        foreach ($pages as $uri => $name) {
            $breadcrumb = $this->findByType($this->extractSchemas($uri), 'BreadcrumbList');

            $this->assertSame('http://localhost/en', $breadcrumb->itemListElement[0]->item, $uri);
            $this->assertSame($name, $breadcrumb->itemListElement[1]->name, $uri);
            $this->assertSame("http://localhost{$uri}", $breadcrumb->itemListElement[1]->item, $uri);
        }
    }

    /** Vérifie que les pages internes à contenu éditorial exposent un WebPage speakable. */
    public function testInnerPagesHaveSpeakable(): void
    {
        foreach (['/en/how-it-works', '/en/use-cases', '/en/faq'] as $uri) {
            $webPage = $this->findByType($this->extractSchemas($uri), 'WebPage');

            $this->assertSame('SpeakableSpecification', $webPage->speakable->{'@type'}, $uri);
            $this->assertNotEmpty($webPage->speakable->cssSelector, $uri);
        }
    }

    /**
     * @param  TestResponse<Response>  $response
     */
    private function assertJsonLdBlocksAreClean(TestResponse $response, int $expectedBlocks): void
    {
        $response->assertOk();
        preg_match("/'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $csp);
        $scripts = $this->jsonLdScripts((string) $response->getContent());

        $this->assertCount($expectedBlocks, $scripts);

        foreach ($scripts as $script) {
            $this->assertSame($csp[1], $script->getAttribute('nonce'));
            $this->assertDoesNotMatchRegularExpression(self::HTML_ENTITY_PATTERN, $script->textContent);
            $this->assertIsObject(json_decode($script->textContent, flags: JSON_THROW_ON_ERROR));
        }
    }

    /**
     * @param  array<int, object>  $schemas
     */
    private function findByType(array $schemas, string $type): object
    {
        $found = collect($schemas)->first(fn (object $schema): bool => ($schema->{'@type'} ?? null) === $type);

        $this->assertIsObject($found, "Aucun schéma {$type}");

        return $found;
    }

    /**
     * @return array<int, object>
     */
    private function extractGraph(string $uri): array
    {
        foreach ($this->extractSchemas($uri) as $schema) {
            if (isset($schema->{'@graph'}) && is_array($schema->{'@graph'})) {
                return $schema->{'@graph'};
            }
        }

        $this->fail("Aucun bloc @graph sur {$uri}");
    }

    /**
     * @return array<int, object>
     */
    private function extractSchemas(string $uri): array
    {
        $response = $this->get($uri);

        $response->assertOk();

        return $this->schemasFromResponse($response);
    }

    /**
     * @param  TestResponse<Response>  $response
     * @return array<int, object>
     */
    private function schemasFromResponse(TestResponse $response): array
    {
        return array_map(
            fn (Element $script): object => json_decode($script->textContent, flags: JSON_THROW_ON_ERROR),
            $this->jsonLdScripts((string) $response->getContent()),
        );
    }

    /**
     * @return array<int, Element>
     */
    private function jsonLdScripts(string $html): array
    {
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        return iterator_to_array($document->querySelectorAll('script[type="application/ld+json"]'), false);
    }
}
