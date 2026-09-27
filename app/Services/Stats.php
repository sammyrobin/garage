<?php

declare(strict_types=1);

namespace Garage\Services;

use Garage\Core\Database;
use Garage\Models\Car;

/** Public collection statistics. Money figures are added only for the owner. */
final class Stats
{
    public static function collection(): array
    {
        $totals = Car::totals();
        $row = Database::query(
            'SELECT COUNT(DISTINCT series_id) AS series,
                    COALESCE(SUM(rarity = ?), 0) AS ths,
                    COALESCE(SUM(is_favorite), 0) AS favorites,
                    MIN(real_year) AS oldest_year,
                    MAX(real_year) AS newest_year
               FROM cars',
            ['treasure_hunt']
        )->fetch();

        $rarities = array_fill_keys(Car::RARITIES, 0);
        foreach (Database::query('SELECT rarity, COUNT(*) AS n FROM cars WHERE rarity IS NOT NULL GROUP BY rarity')->fetchAll() as $r) {
            $rarities[$r['rarity']] = (int) $r['n'];
        }

        $oldest = Database::query(
            'SELECT c.id, c.slug, c.name, c.model, c.real_year, b.name AS brand_name, b.slug AS brand_slug
               FROM cars c JOIN brands b ON b.id = c.brand_id
              WHERE c.real_year IS NOT NULL
           ORDER BY c.real_year ASC, c.id ASC LIMIT 1'
        )->fetch() ?: null;

        $sths = Database::query(
            'SELECT c.id, c.slug, c.name, c.model, c.real_year, c.rarity, c.item_condition, c.is_favorite,
                    b.name AS brand_name, b.slug AS brand_slug, NULL AS series_name
               FROM cars c JOIN brands b ON b.id = c.brand_id
              WHERE c.rarity = ?
           ORDER BY c.created_at DESC LIMIT 12',
            ['super_treasure_hunt']
        )->fetchAll();

        return [
            'cars' => (int) $totals['cars'],
            'brands' => (int) $totals['brands'],
            'series' => (int) $row['series'],
            'sths' => (int) $totals['sths'],
            'ths' => (int) $row['ths'],
            'favorites' => (int) $row['favorites'],
            'oldest_year' => $row['oldest_year'] !== null ? (int) $row['oldest_year'] : null,
            'newest_year' => $row['newest_year'] !== null ? (int) $row['newest_year'] : null,
            'rarities' => $rarities,
            'top_brands' => Database::query(
                'SELECT b.name, b.slug, COUNT(*) AS n FROM cars c JOIN brands b ON b.id = c.brand_id
              GROUP BY b.id ORDER BY n DESC, b.name LIMIT 10'
            )->fetchAll(),
            'oldest' => $oldest ? Catalog::withPhotos([$oldest], ['front'])[0] : null,
            'sth_cars' => Catalog::withPhotos($sths),
            'invested' => (float) $totals['invested'],
            'average' => $totals['average'] !== null ? (float) $totals['average'] : null,
        ];
    }

    /** Short facts for the marquee ribbon. */
    public static function ribbon(): array
    {
        $s = Database::query(
            'SELECT COUNT(*) AS cars, COUNT(DISTINCT brand_id) AS brands,
                    COALESCE(SUM(rarity = ?), 0) AS sths, COALESCE(SUM(rarity = ?), 0) AS ths,
                    COALESCE(SUM(is_favorite), 0) AS favorites, MIN(real_year) AS oldest
               FROM cars',
            ['super_treasure_hunt', 'treasure_hunt']
        )->fetch();

        return array_map(static fn ($v) => $v === null ? null : (int) $v, $s);
    }
}
