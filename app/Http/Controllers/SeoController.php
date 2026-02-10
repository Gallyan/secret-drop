<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $sitemap = url('/sitemap.xml');
        $content = <<<TXT
            User-agent: *
            Disallow: /s/
            Disallow: /admin/
            Disallow: /api/
            Disallow: /contact

            Sitemap: {$sitemap}
            TXT;

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }

    public function sitemap(): Response
    {
        return response(
            view('sitemap')->render(),
            200,
            ['Content-Type' => 'application/xml']
        );
    }

    public function sitemapStylesheet(): Response
    {
        return response(
            file_get_contents(resource_path('sitemap.xsl')),
            200,
            ['Content-Type' => 'text/xsl']
        );
    }
}
