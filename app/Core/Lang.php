<?php

declare(strict_types=1);

namespace Garage\Core;

/** ES (default, no URL prefix) and EN (/en/...). Strings live in app/lang/{lang}.php. */
final class Lang
{
    public const DEFAULT = 'es';
    public const SUPPORTED = ['es', 'en'];

    private static string $current = self::DEFAULT;
    private static array $strings = [];

    /** Split "/en/admin" into ['en', '/admin']; "/admin" into ['es', '/admin']. */
    public static function split(string $path): array
    {
        foreach (self::SUPPORTED as $lang) {
            if ($lang === self::DEFAULT) {
                continue;
            }
            if ($path === '/' . $lang || str_starts_with($path, '/' . $lang . '/')) {
                return [$lang, substr($path, strlen($lang) + 1) ?: '/'];
            }
        }

        return [self::DEFAULT, $path];
    }

    public static function set(string $lang): void
    {
        self::$current = in_array($lang, self::SUPPORTED, true) ? $lang : self::DEFAULT;
    }

    public static function current(): string
    {
        return self::$current;
    }

    public static function get(string $key, array $replace = []): string
    {
        $text = self::strings(self::$current)[$key] ?? self::strings(self::DEFAULT)[$key] ?? $key;
        foreach ($replace as $name => $value) {
            $text = str_replace(':' . $name, (string) $value, $text);
        }

        return $text;
    }

    private static function strings(string $lang): array
    {
        return self::$strings[$lang] ??= require GARAGE_APP . '/lang/' . $lang . '.php';
    }
}
