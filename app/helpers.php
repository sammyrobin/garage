<?php

declare(strict_types=1);

use Garage\Controllers\BrandStyleController;
use Garage\Core\Config;
use Garage\Core\Csrf;
use Garage\Core\Lang;

/** Escape a value for safe HTML output. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Read a configuration value using dot notation. */
function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/** Translate a key into the current language. */
function t(string $key, array $replace = []): string
{
    return Lang::get($key, $replace);
}

/**
 * Build an app URL under the base path, in the current language.
 * url('/admin') → /garage/admin (ES) or /garage/en/admin (EN)
 */
function url(string $path = '/', ?string $lang = null): string
{
    $lang ??= Lang::current();
    $path = '/' . ltrim($path, '/');
    $prefix = $lang === Lang::DEFAULT ? '' : '/' . $lang;
    $full = $prefix . ($path === '/' && $prefix !== '' ? '/' : $path);

    return rtrim((string) Config::get('app.base_path', ''), '/') . $full;
}

/** URL of a static asset, versioned by modification time for cache busting. */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = GARAGE_ROOT . '/assets/' . $path;
    $version = is_file($file) ? '?v=' . filemtime($file) : '';

    return rtrim((string) Config::get('app.base_path', ''), '/') . '/assets/' . $path . $version;
}

/** Absolute URL (for e-mails, Open Graph, sitemap). */
function absolute_url(string $path = '/', ?string $lang = null): string
{
    $base = (string) Config::get('app.url', '');
    $basePath = rtrim((string) Config::get('app.base_path', ''), '/');
    $origin = $basePath !== '' && str_ends_with($base, $basePath)
        ? substr($base, 0, -strlen($basePath))
        : rtrim($base, '/');

    return $origin . url($path, $lang);
}

/** Hidden input with the CSRF token. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

/** Format an amount in Mexican pesos. */
function money(float|int|string|null $amount): string
{
    return '$' . number_format((float) $amount, 2) . ' MXN';
}

/** Versioned URL of the generated brand-colors stylesheet (never breaks a page if the DB is down). */
function brands_css_url(): string
{
    try {
        $version = BrandStyleController::version();
    } catch (Throwable) {
        $version = '0';
    }

    return rtrim((string) Config::get('app.base_path', ''), '/') . '/brands.css?v=' . $version;
}
