<?php

declare(strict_types=1);

namespace Garage\Controllers;

use Garage\Core\Database;
use Garage\Core\Request;
use Garage\Core\Response;
use Garage\Models\Brand;

/**
 * GET /brands.css — one custom-property pair per brand, so chips get their accent
 * color without inline styles (the CSP forbids them).
 * Link it with a version (?v=max updated_at) so the browser can cache it long-term.
 */
final class BrandStyleController
{
    public function show(Request $request): Response
    {
        $css = "/* Generated from the brands table. */\n";
        foreach (Database::query('SELECT slug, accent_color FROM brands ORDER BY id')->fetchAll() as $brand) {
            if (!preg_match('/^[a-z0-9-]+$/', $brand['slug']) || !Brand::isValidColor($brand['accent_color'])) {
                continue;
            }
            $css .= sprintf(
                "[data-brand=\"%s\"]{--brand:%s;--brand-text:%s}\n",
                $brand['slug'],
                $brand['accent_color'],
                Brand::textColor($brand['accent_color'])
            );
        }

        return (new Response($css))
            ->header('Content-Type', 'text/css; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=31536000, immutable');
    }

    /** Cache-busting version for the <link> tag. */
    public static function version(): string
    {
        return substr(md5((string) Database::query('SELECT CONCAT(COUNT(*), MAX(updated_at)) FROM brands')->fetchColumn()), 0, 10);
    }
}
