<?php

declare(strict_types=1);

/**
 * Sends the grouped new-car e-mail once the 10-minute window has closed.
 * Meant for a cPanel cron job every 5 minutes, e.g.:
 *   /usr/local/bin/php /home/USER/public_html/garage/bin/notify.php
 * (The admin panel also flushes lazily, so the cron is a safety net.)
 */

use Garage\Services\NotificationService;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

echo NotificationService::flushDue() ? "Summary sent.\n" : "Nothing to send.\n";
