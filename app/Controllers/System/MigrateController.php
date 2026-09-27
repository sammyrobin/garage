<?php

declare(strict_types=1);

namespace Garage\Controllers\System;

use Garage\Core\Config;
use Garage\Core\Database;
use Garage\Core\Logger;
use Garage\Core\Migrator;
use Garage\Core\Request;
use Garage\Core\Response;
use Garage\Support\Installer;

/**
 * Applies migrations added after the first install (setup.php runs only once):
 *   curl -X POST -H "X-Migrate-Token: <migrate_token>" https://samueltorres.dev/garage/_migrate
 * Wrong method, missing or wrong token → plain 404,
 * indistinguishable from any unknown URL.
 */
final class MigrateController
{
    public function run(Request $request): Response
    {
        $expected = (string) Config::get('migrate_token', '');
        $sent = (string) $request->header('X-Migrate-Token');

        if (!$request->isPost() || strlen($expected) < 32 || !hash_equals($expected, $sent)) {
            return Response::notFound();
        }

        try {
            $installed = Installer::ensureRuntimeFolders();
            $result = (new Migrator(Database::pdo(), GARAGE_ROOT . '/migrations'))->run();
        } catch (\Throwable $e) {
            Logger::error('Migration failed: ' . $e->getMessage());
            return Response::json(['ok' => false, 'error' => 'Migration failed, see server log.'], 500);
        }

        return Response::json(['ok' => true, 'installed' => $installed] + $result);
    }
}
