<?php

declare(strict_types=1);

/**
 * GARAGE — front controller.
 * Every request that is not a static file is routed through here (see .htaccess).
 */

$app = require __DIR__ . '/app/bootstrap.php';
$app->run();
