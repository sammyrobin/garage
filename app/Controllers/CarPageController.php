<?php

declare(strict_types=1);

namespace Garage\Controllers;

use Garage\Core\Database;
use Garage\Core\Request;
use Garage\Core\Response;
use Garage\Models\Car;
use Garage\Models\CarPhoto;
use Garage\Services\Catalog;
use Garage\Services\PhotoStorage;

/** /auto/{slug} (ES) · /en/car/{slug} (EN): 5-angle viewer, specs, related cars. */
final class CarPageController extends Controller
{
    public function show(Request $request, array $params): Response
    {
        $car = Car::findBySlug((string) ($params['slug'] ?? ''));
        if (!$car) {
            return Response::notFound();
        }
        unset($car['cost_mxn']);   // private: never reaches a public template

        $photos = CarPhoto::forCar((int) $car['id']);
        $related = Database::query(
            'SELECT c.id, c.slug, c.name, c.model, c.real_year, c.rarity, c.item_condition, c.is_favorite,
                    b.name AS brand_name, b.slug AS brand_slug, NULL AS series_name
               FROM cars c JOIN brands b ON b.id = c.brand_id
              WHERE c.brand_id = ? AND c.id <> ?
           ORDER BY c.is_favorite DESC, c.created_at DESC LIMIT 6',
            [$car['brand_id'], $car['id']]
        )->fetchAll();

        $this->alternates(route_path('car', ['slug' => $car['slug']], 'es'), route_path('car', ['slug' => $car['slug']], 'en'));

        return $this->view('public/car', [
            'title' => $car['name'] . ' — ' . $car['brand_name'] . ' · GARAGE',
            'description' => t('car.meta', ['name' => $car['name'], 'brand' => $car['brand_name'], 'model' => $car['model']]),
            'ogImage' => isset($photos['front']) ? PhotoStorage::absoluteUrl($photos['front']['file_key'], 'lg') : null,
            'car' => $car,
            'photos' => $photos,
            'related' => Catalog::withPhotos($related),
        ]);
    }
}
