<?php

namespace Tests\Feature;

use Tests\TestCase;

class SchemaJsonLdTest extends TestCase
{
    public function testHomepageHasSingleGraphWithAllSchemaTypes(): void
    {
        $types = collect($this->extractGraph('/en'))
            ->flatMap(function (object $schema): array {
                $type = $schema->{'@type'};

                return is_array($type) ? $type : [$type];
            })
            ->all();

        $this->assertContains('WebSite', $types);
        $this->assertContains('Organization', $types);
        $this->assertContains('Person', $types);
        $this->assertContains('WebApplication', $types);
        $this->assertContains('SoftwareApplication', $types);
    }

    public function testWebApplicationHasRichMetadata(): void
    {
        $app = collect($this->extractGraph('/en'))->first(function ($s) {
            $types = is_array($s->{'@type'}) ? $s->{'@type'} : [$s->{'@type'}];

            return in_array('WebApplication', $types, true);
        });

        $this->assertNotNull($app, 'Graph should contain a WebApplication entity');
        $this->assertNotEmpty($app->license ?? null, 'WebApplication should declare a license');
        $this->assertNotEmpty($app->dateCreated ?? null, 'WebApplication should have dateCreated');
        $this->assertNotEmpty($app->datePublished ?? null, 'WebApplication should have datePublished');
        $this->assertNotEmpty($app->creator ?? null, 'WebApplication should have creator');
        $this->assertNotEmpty($app->publisher ?? null, 'WebApplication should have publisher');
        $this->assertNotEmpty($app->screenshot ?? null, 'WebApplication should have screenshot');
        $this->assertNotEmpty($app->potentialAction ?? null, 'WebApplication should have potentialAction');
        $this->assertSame('CreateAction', $app->potentialAction->{'@type'} ?? null);
    }

    public function testHomepageSchemasHaveIds(): void
    {
        foreach ($this->extractGraph('/en') as $item) {
            $type = is_array($item->{'@type'}) ? implode(',', $item->{'@type'}) : $item->{'@type'};
            $this->assertNotEmpty(
                $item->{'@id'} ?? null,
                "Schema @type={$type} should have @id",
            );
        }
    }

    public function testOrganizationHasFoundingDateAndContactPoint(): void
    {
        $org = collect($this->extractGraph('/en'))->first(fn ($s) => $s->{'@type'} === 'Organization');

        $this->assertNotEmpty($org->foundingDate ?? null, 'Organization should have foundingDate');
        $this->assertNotEmpty($org->contactPoint ?? null, 'Organization should have contactPoint');
    }

    public function testPersonHasWorksFor(): void
    {
        $person = collect($this->extractGraph('/en'))->first(fn ($s) => $s->{'@type'} === 'Person');

        $this->assertNotEmpty($person->worksFor ?? null, 'Person should have worksFor');
    }

    public function testFaqAnswersContainNoHtml(): void
    {
        $schemas = $this->extractSchemas('/en/faq');
        $faq = collect($schemas)->first(fn ($s) => ($s->{'@type'} ?? null) === 'FAQPage');

        $this->assertNotNull($faq, 'FAQ page should have FAQPage schema');

        foreach ($faq->mainEntity as $question) {
            $text = $question->acceptedAnswer->text ?? '';
            $this->assertStringNotContainsString('<a', $text, "FAQ answer contains raw HTML: {$question->name}");
            $this->assertStringNotContainsString('&lt;', $text, "FAQ answer contains escaped HTML: {$question->name}");
        }
    }

    public function testInnerPagesHaveBreadcrumbList(): void
    {
        $pages = ['/en/how-it-works', '/en/use-cases', '/en/faq'];

        foreach ($pages as $page) {
            $schemas = $this->extractSchemas($page);
            $breadcrumb = collect($schemas)->first(fn ($s) => ($s->{'@type'} ?? null) === 'BreadcrumbList');
            $this->assertNotNull($breadcrumb, "{$page} should have BreadcrumbList schema");
        }
    }

    public function testInnerPagesHaveSpeakable(): void
    {
        $pages = ['/en/how-it-works', '/en/use-cases', '/en/faq'];

        foreach ($pages as $page) {
            $schemas = $this->extractSchemas($page);
            $webPage = collect($schemas)->first(fn ($s) => ($s->{'@type'} ?? null) === 'WebPage');
            $this->assertNotNull($webPage, "{$page} should have WebPage schema");
            $this->assertNotEmpty($webPage->speakable ?? null, "{$page} WebPage should have speakable");
        }
    }

    /**
     * @return array<int, object>
     */
    private function extractGraph(string $uri): array
    {
        foreach ($this->extractSchemas($uri) as $schema) {
            if (isset($schema->{'@graph'}) && is_array($schema->{'@graph'})) {
                return array_values(array_filter($schema->{'@graph'}, 'is_object'));
            }
        }

        $this->fail("No @graph block found on {$uri}");
    }

    /**
     * @return array<int, object>
     */
    private function extractSchemas(string $uri): array
    {
        $response = $this->get($uri);
        $html = $response->getContent();

        preg_match_all('/<script type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $matches);

        $schemas = [];
        foreach ($matches[1] as $json) {
            $decoded = json_decode($json);
            $this->assertNotNull($decoded, "Invalid JSON-LD found on {$uri}");
            $schemas[] = $decoded;
        }

        return $schemas;
    }
}
