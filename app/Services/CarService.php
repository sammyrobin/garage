<?php

declare(strict_types=1);

namespace Garage\Services;

use Garage\Core\Database;
use Garage\Core\Logger;
use Garage\Models\Car;
use Garage\Models\CarPhoto;

/**
 * Saves cars together with their angle photos.
 * Callers MUST have authorized the request (Auth::authorize) before calling any
 * method here: this is where uploaded files are first touched.
 */
final class CarService
{
    /**
     * Check every photo slot before anything is written.
     *
     * @param array<string, ?array> $files   angle => $_FILES entry
     * @param string[]              $remove  angles the user asked to remove
     * @param array<string, array>  $current existing photos by angle (edit)
     * @return array<string, string> angle => translation key
     */
    public static function validatePhotos(array $files, array $remove = [], array $current = []): array
    {
        $errors = [];
        foreach (CarPhoto::ANGLES as $angle) {
            $file = $files[$angle] ?? null;
            if (!ImageProcessor::hasFile($file)) {
                continue;
            }
            try {
                ImageProcessor::validate($file);
            } catch (PhotoException $e) {
                $errors[$angle] = $e->getMessage();
            }
        }

        $frontAfter = ImageProcessor::hasFile($files['front'] ?? null)
            || (isset($current['front']) && !in_array('front', $remove, true));
        if (!$frontAfter && !isset($errors['front'])) {
            $errors['front'] = 'photo.front_required';
        }

        return $errors;
    }

    /** @param array<string, ?array> $files */
    public static function create(array $data, array $files): int
    {
        $processed = self::processAll($files);

        $pdo = Database::pdo();
        try {
            $pdo->beginTransaction();
            $carId = Car::create($data);
            foreach ($processed as $angle => $photo) {
                CarPhoto::upsert($carId, $angle, $photo);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($processed as $photo) {
                PhotoStorage::delete($photo['key']);
            }
            throw $e;
        }

        return $carId;
    }

    /**
     * @param array<string, ?array> $files
     * @param string[]              $remove
     */
    public static function update(int $carId, array $data, array $files, array $remove): void
    {
        $processed = self::processAll($files);
        $obsolete = [];

        $pdo = Database::pdo();
        try {
            $pdo->beginTransaction();
            Car::update($carId, $data);
            foreach ($processed as $angle => $photo) {
                if ($old = CarPhoto::upsert($carId, $angle, $photo)) {
                    $obsolete[] = $old['file_key'];
                }
            }
            foreach ($remove as $angle) {
                if (!isset($processed[$angle]) && ($old = CarPhoto::remove($carId, $angle))) {
                    $obsolete[] = $old['file_key'];
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($processed as $photo) {
                PhotoStorage::delete($photo['key']);
            }
            throw $e;
        }

        // Old files go only after the database no longer points at them.
        foreach ($obsolete as $key) {
            PhotoStorage::delete($key);
        }
    }

    public static function delete(int $carId): void
    {
        $photos = CarPhoto::forCar($carId);
        Car::delete($carId);
        foreach ($photos as $photo) {
            PhotoStorage::delete($photo['file_key']);
        }
        Logger::info('Car deleted', ['id' => $carId]);
    }

    /** @return array<string, array> angle => processed photo */
    private static function processAll(array $files): array
    {
        $processed = [];
        try {
            foreach (CarPhoto::ANGLES as $angle) {
                $file = $files[$angle] ?? null;
                if (ImageProcessor::hasFile($file)) {
                    $processed[$angle] = ImageProcessor::process($file, $angle === 'front');
                }
            }
        } catch (\Throwable $e) {
            foreach ($processed as $photo) {
                PhotoStorage::delete($photo['key']);
            }
            throw $e;
        }

        return $processed;
    }
}
