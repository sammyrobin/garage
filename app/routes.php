<?php

declare(strict_types=1);

use Garage\Controllers\Admin\BrandController;
use Garage\Controllers\Admin\CarController;
use Garage\Controllers\Admin\CsvController;
use Garage\Controllers\Admin\DashboardController;
use Garage\Controllers\Admin\SeriesController;
use Garage\Controllers\Admin\SessionController;
use Garage\Controllers\BrandStyleController;
use Garage\Controllers\HomeController;
use Garage\Controllers\System\MigrateController;
use Garage\Core\Router;

// Paths are relative to the base path and without the language prefix:
// "/admin" answers /garage/admin and /garage/en/admin.
return (new Router())
    ->get('/', [HomeController::class, 'index'])
    ->get('/brands.css', [BrandStyleController::class, 'show'])

    // Admin panel: public to browse (exhibition mode); every POST needs the owner.
    ->get('/admin', [DashboardController::class, 'index'])
    ->post('/admin/session', [SessionController::class, 'store'])
    ->post('/admin/logout', [SessionController::class, 'destroy'])

    ->get('/admin/cars', [CarController::class, 'index'])
    ->get('/admin/cars/new', [CarController::class, 'create'])
    ->post('/admin/cars', [CarController::class, 'store'])
    ->get('/admin/cars/{id}/edit', [CarController::class, 'edit'])
    ->post('/admin/cars/{id}', [CarController::class, 'update'])
    ->post('/admin/cars/{id}/delete', [CarController::class, 'destroy'])

    ->get('/admin/brands', [BrandController::class, 'index'])
    ->post('/admin/brands', [BrandController::class, 'store'])
    ->post('/admin/brands/{id}', [BrandController::class, 'update'])
    ->post('/admin/brands/{id}/delete', [BrandController::class, 'destroy'])

    ->get('/admin/series', [SeriesController::class, 'index'])
    ->post('/admin/series', [SeriesController::class, 'store'])
    ->post('/admin/series/{id}', [SeriesController::class, 'update'])
    ->post('/admin/series/{id}/delete', [SeriesController::class, 'destroy'])

    ->get('/admin/csv', [CsvController::class, 'index'])
    ->post('/admin/csv/export', [CsvController::class, 'export'])
    ->post('/admin/csv/import', [CsvController::class, 'import'])

    // Deployment hook. Any request without the right token gets a 404.
    ->any('/_migrate', [MigrateController::class, 'run']);
