<?php

declare(strict_types=1);

namespace Garage\Models;

use Garage\Core\Database;
use Garage\Support\Str;

final class Car
{
    public const RARITIES = ['mainline', 'treasure_hunt', 'super_treasure_hunt', 'premium', 'red_line_club', 'limited', 'other'];
    public const CONDITIONS = ['carded', 'loose', 'damaged'];

    /** Columns written from validated input (see CarInput). */
    public const FIELDS = [
        'name', 'brand_id', 'model', 'cost_mxn', 'series_id', 'real_year', 'casting_year',
        'collection_number', 'color', 'rarity', 'item_condition', 'acquired_at', 'notes', 'is_favorite',
    ];

    private const SELECT = 'SELECT c.*, b.name AS brand_name, b.slug AS brand_slug, b.accent_color AS brand_color,
                                   s.name AS series_name, s.slug AS series_slug
                              FROM cars c
                              JOIN brands b ON b.id = c.brand_id
                         LEFT JOIN series s ON s.id = c.series_id';

    public static function find(int $id): ?array
    {
        return Database::query(self::SELECT . ' WHERE c.id = ?', [$id])->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::query(self::SELECT . ' WHERE c.slug = ?', [$slug])->fetch() ?: null;
    }

    /** @param int[] $ids */
    public static function findMany(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        return Database::query(self::SELECT . " WHERE c.id IN ({$placeholders}) ORDER BY c.created_at, c.id", array_values($ids))->fetchAll();
    }

    /** Admin list: text search + pagination. */
    public static function paginate(string $search, int $page, int $perPage): array
    {
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = ' WHERE (c.name LIKE ? OR c.model LIKE ? OR b.name LIKE ?)';
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $params = [$like, $like, $like];
        }

        $total = (int) Database::query(
            'SELECT COUNT(*) FROM cars c JOIN brands b ON b.id = c.brand_id' . $where,
            $params
        )->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::query(
            self::SELECT . $where . ' ORDER BY c.created_at DESC, c.id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total, 'pages' => max(1, (int) ceil($total / $perPage)), 'page' => $page];
    }

    public static function all(): array
    {
        return Database::query(self::SELECT . ' ORDER BY c.created_at, c.id')->fetchAll();
    }

    public static function create(array $data): int
    {
        // Column names come only from the FIELDS whitelist, never from input keys.
        $data = array_intersect_key($data, array_flip(self::FIELDS));
        $data['slug'] = self::uniqueSlug((string) $data['name']);
        $columns = array_keys($data);
        Database::query(
            'INSERT INTO cars (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')',
            array_values($data)
        );

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $data = array_intersect_key($data, array_flip(self::FIELDS));
        if ($data === []) {
            return;
        }
        $sets = implode(', ', array_map(static fn (string $col): string => $col . ' = ?', array_keys($data)));
        Database::query('UPDATE cars SET ' . $sets . ' WHERE id = ?', [...array_values($data), $id]);
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM cars WHERE id = ?', [$id]);
    }

    /** Collection totals; money figures are private and must be filtered by the caller. */
    public static function totals(): array
    {
        return Database::query(
            'SELECT COUNT(*) AS cars,
                    COUNT(DISTINCT brand_id) AS brands,
                    COALESCE(SUM(rarity = ?), 0) AS sths,
                    COALESCE(SUM(cost_mxn), 0) AS invested,
                    AVG(cost_mxn) AS average,
                    COUNT(cost_mxn) AS priced
               FROM cars',
            ['super_treasure_hunt']
        )->fetch();
    }

    private static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name, 150);
        $slug = $base;
        for ($i = 2; ; $i++) {
            if (!Database::query('SELECT 1 FROM cars WHERE slug = ?', [$slug])->fetch()) {
                return $slug;
            }
            $slug = $base . '-' . $i;
        }
    }
}
