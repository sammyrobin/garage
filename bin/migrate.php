<?php

declare(strict_types=1);

/**
 * Run pending migrations from the command line (local development).
 *   docker compose exec app php bin/migrate.php
 * In production the deploy workflow calls POST /_migrate instead (no SSH on shared hosting).
 */

use Garage\Core\Database;
use Garage\Core\Migrator;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$result = (new Migrator(Database::pdo(), GARAGE_ROOT . '/migrations'))->run();

foreach ($result['applied'] as $version) {
    echo "  applied  {$version}\n";
}
printf("Done: %d applied, %d already up to date.\n", count($result['applied']), $result['skipped']);
