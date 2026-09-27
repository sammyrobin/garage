<?php

declare(strict_types=1);

namespace Garage\Support;

/**
 * Server-side setup that the FTP deploy cannot do: the deploy never touches uploads/
 * or storage/ (so photos and logs survive), therefore the protective .htaccess of
 * uploads/ and the runtime folders are (re)installed here, on every migration run.
 */
final class Installer
{
    /** @return string[] what was created or refreshed */
    public static function ensureRuntimeFolders(): array
    {
        $done = [];
        foreach (['uploads', 'storage/logs', 'storage/cache'] as $dir) {
            $path = GARAGE_ROOT . '/' . $dir;
            if (!is_dir($path) && mkdir($path, 0755, true)) {
                $done[] = $dir . '/';
            }
        }

        $stub = GARAGE_APP . '/stubs/uploads.htaccess';
        $target = GARAGE_ROOT . '/uploads/.htaccess';
        if (!is_file($target) || md5_file($target) !== md5_file($stub)) {
            if (!copy($stub, $target)) {
                throw new \RuntimeException('Could not install uploads/.htaccess');
            }
            $done[] = 'uploads/.htaccess';
        }

        return $done;
    }
}
