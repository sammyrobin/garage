<?php

declare(strict_types=1);

use Garage\Core\App;
use Garage\Core\Config;
use Garage\Core\ErrorHandler;

define('GARAGE_ROOT', dirname(__DIR__));
define('GARAGE_APP', __DIR__);

// PSR-4 autoloader: Garage\Foo\Bar → app/Foo/Bar.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'Garage\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = GARAGE_APP . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require GARAGE_APP . '/helpers.php';

$configFile = GARAGE_APP . '/config.php';
if (!is_file($configFile)) {
    http_response_code(503);
    error_log('GARAGE: app/config.php is missing');
    exit('Service unavailable.');
}

Config::load(require $configFile);
date_default_timezone_set((string) Config::get('app.timezone', 'UTC'));

ErrorHandler::register(Config::get('app.env') !== 'production');

return new App(require GARAGE_APP . '/routes.php');
