<?php

namespace Tests\Feature;

use App\Support\PublicUrls;
use Tests\TestCase;

class IndexNowSitemapConsistencyTest extends TestCase
{
    /** Vérifie que les URLs soumises à IndexNow sont exactement les <loc> du sitemap. */
    public function testPublicUrlsMatchTheSitemapLocations(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();

        $this->assertSame($this->sitemapLocations($response->getContent()), PublicUrls::all());
    }

    /** Vérifie que le sitemap et la liste partagée exposent bien 55 URLs uniques. */
    public function testPublicUrlsAreUniqueAndComplete(): void
    {
        $urls = PublicUrls::all();

        $this->assertCount(55, $urls);
        $this->assertSame($urls, array_values(array_unique($urls)));
    }

    /**
     * @return array<int, string>
     */
    private function sitemapLocations(string $xml): array
    {
        preg_match_all('#<loc>([^<]+)</loc>#', $xml, $matches);

        return array_map(
            fn (string $location): string => html_entity_decode($location, ENT_QUOTES | ENT_XML1, 'UTF-8'),
            $matches[1]
        );
    }
}
