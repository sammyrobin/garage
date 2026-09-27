<?php

declare(strict_types=1);

namespace Garage\Services;

use Garage\Core\Config;

/**
 * Where processed photos live: uploads/{k[0..1]}/{key}-{size}.webp
 * The key is random (random_bytes); the uploaded file name is never used.
 */
final class PhotoStorage
{
    /** Longest side in px and WebP quality for each variant. */
    public const SIZES = [
        'lg' => [1600, 80],
        'md' => [800, 78],
        'sm' => [400, 75],
    ];

    /** Small JPEG of the front photo for e-mails (Outlook desktop cannot show WebP). */
    public const EMAIL = [240, 82];

    public static function newKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function path(string $key, string $size): string
    {
        return self::dir() . '/' . self::relative($key, $size);
    }

    public static function url(string $key, string $size = 'md'): string
    {
        return rtrim((string) Config::get('app.base_path', ''), '/') . '/uploads/' . self::relative($key, $size);
    }

    public static function absoluteUrl(string $key, string $size = 'md'): string
    {
        return rtrim((string) Config::get('app.url', ''), '/') . '/uploads/' . self::relative($key, $size);
    }

    public static function delete(string $key): void
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $key)) {
            return;
        }
        foreach ([...array_keys(self::SIZES), 'em'] as $size) {
            $file = self::path($key, $size);
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    public static function dir(): string
    {
        return GARAGE_ROOT . '/uploads';
    }

    private static function relative(string $key, string $size): string
    {
        $ext = $size === 'em' ? 'jpg' : 'webp';

        return substr($key, 0, 2) . '/' . $key . '-' . $size . '.' . $ext;
    }
}
