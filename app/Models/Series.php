<?php

declare(strict_types=1);

namespace Garage\Models;

use Garage\Core\Database;
use Garage\Support\Str;

final class Series
{
    public static function all(): array
    {
        return Database::query(
            'SELECT s.*, COUNT(c.id) AS cars_count
               FROM series s LEFT JOIN cars c ON c.series_id = s.id
           GROUP BY s.id
           ORDER BY s.sort_order, s.name'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        return Database::query('SELECT * FROM series WHERE id = ?', [$id])->fetch() ?: null;
    }

    public static function findByName(string $name): ?array
    {
        return Database::query('SELECT * FROM series WHERE name = ?', [$name])->fetch() ?: null;
    }

    public static function create(string $name): int
    {
        Database::query('INSERT INTO series (name, slug, sort_order) VALUES (?, ?, 500)', [$name, self::uniqueSlug($name)]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $name): void
    {
        Database::query('UPDATE series SET name = ?, slug = ? WHERE id = ?', [$name, self::uniqueSlug($name, $id), $id]);
    }

    /** Cars keep existing; their series becomes empty (FK ON DELETE SET NULL). */
    public static function delete(int $id): void
    {
        Database::query('DELETE FROM series WHERE id = ?', [$id]);
    }

    private static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name, 80);
        $slug = $base;
        for ($i = 2; ; $i++) {
            $taken = Database::query('SELECT id FROM series WHERE slug = ? AND id <> ?', [$slug, $ignoreId ?? 0])->fetch();
            if (!$taken) {
                return $slug;
            }
            $slug = $base . '-' . $i;
        }
    }
}
