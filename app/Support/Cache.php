<?php

declare(strict_types=1);

namespace Garage\Support;

/** Tiny JSON file cache in storage/cache (web-blocked). */
final class Cache
{
    public static function remember(string $name, int $ttl, callable $compute): mixed
    {
        $file = self::file($name);
        if (is_file($file) && filemtime($file) > time() - $ttl) {
            $cached = json_decode((string) file_get_contents($file), true);
            if (is_array($cached) && array_key_exists('value', $cached)) {
                return $cached['value'];
            }
        }

        $value = $compute();
        self::put($name, $value);

        return $value;
    }

    public static function put(string $name, mixed $value): void
    {
        $file = self::file($name);
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($file, json_encode(['value' => $value]), LOCK_EX);
    }

    public static function forget(string $name): void
    {
        @unlink(self::file($name));
    }

    private static function file(string $name): string
    {
        return GARAGE_ROOT . '/storage/cache/' . preg_replace('/[^a-z0-9_-]/', '', $name) . '.json';
    }
}
