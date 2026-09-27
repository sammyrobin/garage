<?php

declare(strict_types=1);

namespace Garage\Services;

use Garage\Core\Database;
use Garage\Models\Car;
use Garage\Models\CarPhoto;
use Garage\Support\Str;

/**
 * Public gallery query: combinable filters that live in the URL, so every view is shareable.
 *   ?q=skyline&brand=nissan,toyota&series=car-culture&rarity=super_treasure_hunt
 *   &condition=carded&from=1990&to=1999&sort=year&page=2
 */
final class Catalog
{
    public const PER_PAGE = 24;
    public const SORTS = ['recent', 'year', 'brand', 'favorites'];

    /** Normalize raw query parameters into a filter array (unknown values dropped). */
    public static function filters(array $query): array
    {
        // brand=a,b (JS / shared links) or brand[]=a&brand[]=b (plain form submit).
        $rawBrand = $query['brand'] ?? '';
        $rawBrand = is_array($rawBrand) ? implode(',', array_filter($rawBrand, 'is_string')) : $rawBrand;
        $brands = array_values(array_filter(
            array_map('trim', explode(',', Str::clean($rawBrand))),
            static fn (string $s): bool => (bool) preg_match('/^[a-z0-9-]{1,90}$/', $s)
        ));
        $year = static function (mixed $v): ?int {
            $v = Str::clean($v);
            return ctype_digit($v) && (int) $v >= 1886 && (int) $v <= 2100 ? (int) $v : null;
        };
        $series = Str::clean($query['series'] ?? '');
        $rarity = Str::clean($query['rarity'] ?? '');
        $condition = Str::clean($query['condition'] ?? '');
        $sort = Str::clean($query['sort'] ?? 'recent');

        return [
            'q' => Str::cut(Str::clean($query['q'] ?? ''), 80),
            'brand' => array_slice(array_unique($brands), 0, 20),
            'series' => preg_match('/^[a-z0-9-]{1,90}$/', $series) ? $series : '',
            'rarity' => in_array($rarity, Car::RARITIES, true) ? $rarity : '',
            'condition' => in_array($condition, Car::CONDITIONS, true) ? $condition : '',
            'from' => $year($query['from'] ?? ''),
            'to' => $year($query['to'] ?? ''),
            'sort' => in_array($sort, self::SORTS, true) ? $sort : 'recent',
        ];
    }

    /** Query string for a filter set (empty values omitted), e.g. for links and pagination. */
    public static function queryString(array $filters, array $override = []): string
    {
        $f = array_merge($filters, $override);
        $params = array_filter([
            'q' => $f['q'] ?? '',
            'brand' => implode(',', $f['brand'] ?? []),
            'series' => $f['series'] ?? '',
            'rarity' => $f['rarity'] ?? '',
            'condition' => $f['condition'] ?? '',
            'from' => $f['from'] ?? null,
            'to' => $f['to'] ?? null,
            'sort' => ($f['sort'] ?? 'recent') === 'recent' ? '' : $f['sort'],
            'page' => ($f['page'] ?? 1) > 1 ? $f['page'] : null,
        ], static fn ($v): bool => $v !== '' && $v !== null);

        return $params ? '?' . http_build_query($params) : '';
    }

    public static function isFiltered(array $filters): bool
    {
        return $filters['q'] !== '' || $filters['brand'] || $filters['series'] !== '' || $filters['rarity'] !== ''
            || $filters['condition'] !== '' || $filters['from'] !== null || $filters['to'] !== null;
    }

    /** @return array{cars: array, total: int, page: int, pages: int} cars with 'photos' (front/left/right) */
    public static function search(array $filters, int $page): array
    {
        $where = [];
        $params = [];

        if ($filters['q'] !== '') {
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $where[] = '(c.name LIKE ? OR c.model LIKE ? OR b.name LIKE ? OR c.color LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }
        if ($filters['brand']) {
            $where[] = 'b.slug IN (' . implode(',', array_fill(0, count($filters['brand']), '?')) . ')';
            array_push($params, ...$filters['brand']);
        }
        if ($filters['series'] !== '') {
            $where[] = 's.slug = ?';
            $params[] = $filters['series'];
        }
        if ($filters['rarity'] !== '') {
            $where[] = 'c.rarity = ?';
            $params[] = $filters['rarity'];
        }
        if ($filters['condition'] !== '') {
            $where[] = 'c.item_condition = ?';
            $params[] = $filters['condition'];
        }
        if ($filters['from'] !== null) {
            $where[] = 'c.real_year >= ?';
            $params[] = $filters['from'];
        }
        if ($filters['to'] !== null) {
            $where[] = 'c.real_year <= ?';
            $params[] = $filters['to'];
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $from = ' FROM cars c JOIN brands b ON b.id = c.brand_id LEFT JOIN series s ON s.id = c.series_id';
        $order = match ($filters['sort']) {
            'year' => ' ORDER BY c.real_year IS NULL, c.real_year ASC, c.name',
            'brand' => ' ORDER BY b.name, c.name',
            'favorites' => ' ORDER BY c.is_favorite DESC, c.created_at DESC, c.id DESC',
            default => ' ORDER BY c.created_at DESC, c.id DESC',
        };

        $total = (int) Database::query('SELECT COUNT(*)' . $from . $whereSql, $params)->fetchColumn();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $page), $pages);

        // Public listing: the private cost column is never selected.
        $cars = Database::query(
            'SELECT c.id, c.slug, c.name, c.model, c.real_year, c.casting_year, c.rarity, c.item_condition, c.is_favorite,
                    b.name AS brand_name, b.slug AS brand_slug, s.name AS series_name'
            . $from . $whereSql . $order
            . ' LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE),
            $params
        )->fetchAll();

        return ['cars' => self::withPhotos($cars), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /** Attach front/left/right photos (grid card + hover swap). */
    public static function withPhotos(array $cars, array $angles = ['front', 'left', 'right']): array
    {
        $photos = CarPhoto::forCars(array_map(static fn (array $c): int => (int) $c['id'], $cars), $angles);
        foreach ($cars as &$car) {
            $car['photos'] = $photos[(int) $car['id']] ?? [];
        }

        return $cars;
    }

    /** Cars for the 3D intro: favorites first, then random; front photo required. */
    public static function introCars(int $limit = 48): array
    {
        $cars = Database::query(
            'SELECT c.id, c.slug, c.name, c.is_favorite, p.file_key
               FROM cars c JOIN car_photos p ON p.car_id = c.id AND p.angle = ?
           ORDER BY c.is_favorite DESC, RANDOM()
              LIMIT ' . (int) $limit,
            ['front']
        )->fetchAll();

        return $cars;
    }

    /** Brands that have at least one car (filter chips). */
    public static function brandsInUse(): array
    {
        return Database::query(
            'SELECT b.name, b.slug, COUNT(c.id) AS cars
               FROM brands b JOIN cars c ON c.brand_id = b.id
           GROUP BY b.id ORDER BY cars DESC, b.name'
        )->fetchAll();
    }

    public static function seriesInUse(): array
    {
        return Database::query(
            'SELECT s.name, s.slug FROM series s JOIN cars c ON c.series_id = s.id GROUP BY s.id ORDER BY s.sort_order, s.name'
        )->fetchAll();
    }
}
