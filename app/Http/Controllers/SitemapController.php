<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect(['/', '/quran', '/zikr', '/zikr/adhkar', '/zikr/tasbeeh', '/names', '/prayer', '/qibla', '/learn', '/explore'])
            ->merge(collect(['hadith', 'seerah', 'kids', 'naats', 'ruqyah', 'books'])->map(fn ($m) => "/library/{$m}"))
            ->merge(collect(range(1, 114))->map(fn ($n) => "/quran/{$n}"))
            ->map(fn ($path) => url($path));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>'.e($u)."</loc></url>\n";
        }
        $xml .= "</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml', 'Cache-Control' => 'public, max-age=86400']);
    }
}
