<?php

declare(strict_types=1);

use Garage\Controllers\Admin\DashboardController;
use Garage\Controllers\Admin\SessionController;
use Garage\Controllers\HomeController;
use Garage\Controllers\System\MigrateController;
use Garage\Core\Router;

// Paths are relative to the base path and without the language prefix:
// "/admin" answers /garage/admin and /garage/en/admin.
return (new Router())
    ->get('/', [HomeController::class, 'index'])

    ->get('/admin', [DashboardController::class, 'index'])
    ->post('/admin/session', [SessionController::class, 'store'])
    ->post('/admin/logout', [SessionController::class, 'destroy'])

    // Deployment hook. Any request without the right token gets a 404.
    ->any('/_migrate', [MigrateController::class, 'run']);
