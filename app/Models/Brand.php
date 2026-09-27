<?php

declare(strict_types=1);

namespace Garage\Models;

use Garage\Core\Database;
use Garage\Support\Str;

final class Brand
{
    /** @return array<int, array> ordered for selects and chips */
    public static function all(): array
    {
        return Database::query(
            'SELECT b.*, COUNT(c.id) AS cars_count
               FROM brands b LEFT JOIN cars c ON c.brand_id = b.id
           GROUP BY b.id
           ORDER BY b.sort_order, b.name'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        return Database::query('SELECT * FROM brands WHERE id = ?', [$id])->fetch() ?: null;
    }

    public static function findByName(string $name): ?array
    {
        return Database::query('SELECT * FROM brands WHERE name = ?', [$name])->fetch() ?: null;
    }

    public static function create(string $name, string $color): int
    {
        Database::query(
            'INSERT INTO brands (name, slug, accent_color, sort_order) VALUES (?, ?, ?, 500)',
            [$name, self::uniqueSlug($name), $color]
        );

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $name, string $color): void
    {
        Database::query(
            'UPDATE brands SET name = ?, slug = ?, accent_color = ? WHERE id = ?',
            [$name, self::uniqueSlug($name, $id), $color, $id]
        );
    }

    /** False when cars still use the brand (FK RESTRICT). */
    public static function delete(int $id): bool
    {
        $inUse = (int) Database::query('SELECT COUNT(*) FROM cars WHERE brand_id = ?', [$id])->fetchColumn();
        if ($inUse > 0) {
            return false;
        }
        Database::query('DELETE FROM brands WHERE id = ?', [$id]);

        return true;
    }

    /** Text color (ink or white) with the best contrast on the accent color. */
    public static function textColor(string $hex): string
    {
        $luminance = static function (string $hex): float {
            $rgb = array_map(
                static fn (string $c): float => ($v = hexdec($c) / 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4,
                str_split(ltrim($hex, '#'), 2)
            );

            return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
        };
        $l = $luminance($hex);
        $onWhite = 1.05 / ($l + 0.05);
        $onInk = ($l + 0.05) / ($luminance('#141414') + 0.05);

        return $onWhite >= $onInk ? '#FFFFFF' : '#141414';
    }

    public static function isValidColor(string $hex): bool
    {
        return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', $hex);
    }

    private static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name, 80);
        $slug = $base;
        for ($i = 2; ; $i++) {
            $taken = Database::query('SELECT id FROM brands WHERE slug = ? AND id <> ?', [$slug, $ignoreId ?? 0])->fetch();
            if (!$taken) {
                return $slug;
            }
            $slug = $base . '-' . $i;
        }
    }
}
