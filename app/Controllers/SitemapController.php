<?php

declare(strict_types=1);

namespace Garage\Controllers;

use Garage\Core\Database;
use Garage\Core\Request;
use Garage\Core\Response;

/** Public pages in both languages (the admin panel is intentionally left out). */
final class SitemapController
{
    public function index(Request $request): Response
    {
        $entries = [['home', [], null], ['stats', [], null]];
        foreach (Database::query('SELECT slug, updated_at FROM cars ORDER BY id')->fetchAll() as $car) {
            $entries[] = ['car', ['slug' => $car['slug']], substr((string) $car['updated_at'], 0, 10)];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        foreach ($entries as [$name, $params, $lastmod]) {
            $es = absolute_url(route_path($name, $params, 'es'), 'es');
            $en = absolute_url(route_path($name, $params, 'en'), 'en');
            foreach ([$es, $en] as $loc) {
                $xml .= '  <url><loc>' . e($loc) . '</loc>'
                    . ($lastmod ? '<lastmod>' . e($lastmod) . '</lastmod>' : '')
                    . '<xhtml:link rel="alternate" hreflang="es" href="' . e($es) . '"/>'
                    . '<xhtml:link rel="alternate" hreflang="en" href="' . e($en) . '"/></url>' . "\n";
            }
        }
        $xml .= '</urlset>' . "\n";

        return (new Response($xml))->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
