<?php

declare(strict_types=1);

namespace Garage\Models;

use Garage\Core\Database;

final class CarPhoto
{
    /** Display order; "front" is always the main photo. */
    public const ANGLES = ['front', 'back', 'left', 'right', 'top'];

    /** @return array<string, array> photos keyed by angle, in ANGLES order */
    public static function forCar(int $carId): array
    {
        $rows = Database::query('SELECT * FROM car_photos WHERE car_id = ?', [$carId])->fetchAll();
        $byAngle = array_column($rows, null, 'angle');

        return array_filter(array_merge(array_fill_keys(self::ANGLES, null), $byAngle));
    }

    /**
     * Photos for many cars at once: [car_id => [angle => row]].
     * @param int[] $carIds
     */
    public static function forCars(array $carIds, ?array $angles = null): array
    {
        if ($carIds === []) {
            return [];
        }
        $params = array_values($carIds);
        $sql = 'SELECT * FROM car_photos WHERE car_id IN (' . implode(',', array_fill(0, count($carIds), '?')) . ')';
        if ($angles !== null) {
            $sql .= ' AND angle IN (' . implode(',', array_fill(0, count($angles), '?')) . ')';
            $params = [...$params, ...$angles];
        }

        $out = [];
        foreach (Database::query($sql, $params)->fetchAll() as $row) {
            $out[(int) $row['car_id']][$row['angle']] = $row;
        }

        return $out;
    }

    /** Insert or replace the photo for an angle. Returns the replaced row (to delete its files). */
    public static function upsert(int $carId, string $angle, array $photo): ?array
    {
        $old = Database::query('SELECT * FROM car_photos WHERE car_id = ? AND angle = ?', [$carId, $angle])->fetch() ?: null;
        Database::query(
            'INSERT INTO car_photos (car_id, angle, file_key, width, height, bytes) VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE file_key = VALUES(file_key), width = VALUES(width),
                                     height = VALUES(height), bytes = VALUES(bytes), created_at = NOW()',
            [$carId, $angle, $photo['key'], $photo['width'], $photo['height'], $photo['bytes']]
        );

        return $old;
    }

    public static function remove(int $carId, string $angle): ?array
    {
        $old = Database::query('SELECT * FROM car_photos WHERE car_id = ? AND angle = ?', [$carId, $angle])->fetch() ?: null;
        Database::query('DELETE FROM car_photos WHERE car_id = ? AND angle = ?', [$carId, $angle]);

        return $old;
    }

    public static function totals(): array
    {
        return Database::query('SELECT COUNT(*) AS photos, COALESCE(SUM(bytes), 0) AS bytes FROM car_photos')->fetch();
    }
}
