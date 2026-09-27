<?php

/**
 * GARAGE configuration template.
 *
 * Production: copy to app/config.php, fill it in and upload it by hand to the
 * server (public_html/garage/app/). Local Docker: copy to app/config.local.php.
 * Both files are ignored by git, so a deploy never uploads or deletes them.
 */

return [
    'app' => [
        // 'production' hides errors from visitors and logs them instead.
        'env'       => 'local',
        // Public URL of the app, no trailing slash.
        'url'       => 'http://localhost:8090/garage',
        // URL path where the app is mounted. '' if served from the domain root.
        'base_path' => '/garage',
        // Random string (64+ hex chars) used to HMAC visitor IPs for rate limiting.
        'key'       => 'change-me-to-a-long-random-string',
        'timezone'  => 'America/Mexico_City',
    ],

    // SQLite database file. Empty = storage/garage.sqlite (blocked from the web,
    // ignored by git). The folder must be writable by PHP. No password needed.
    'db' => [
        'path' => '',
    ],

    'admin' => [
        // Output of password_hash(). Set it with: php bin/set-password.php
        // The plain password never lives on the server or in the repo.
        'password_hash'   => '',
        // Owner session lifetime (inactivity), in seconds.
        'session_ttl'     => 1800,
        // Failed password attempts allowed per real IP within the window.
        'max_attempts'    => 5,
        'attempt_window'  => 900,
    ],

    'session' => [
        // true in production (Cloudflare serves HTTPS even though the origin sees HTTP).
        'secure' => false,
    ],

    // Token required by POST /_migrate (header X-Migrate-Token). Empty disables the endpoint.
    'migrate_token' => '',

    'mail' => [
        'host'       => 'localhost',
        'port'       => 587,
        'secure'     => 'tls',
        'verify_tls' => false,
        'username'   => 'contacto@samueltorres.dev',
        'password'   => '',
        'from_email' => 'contacto@samueltorres.dev',
        'from_name'  => 'GARAGE',
        'notify_to'  => 'samuelt@conqr.mx',
    ],

    // UAPI token with the Quota permission (cPanel → Manage API Tokens). Server-side only.
    'cpanel' => [
        'host'       => '',
        'user'       => '',
        'token'      => '',
        'verify_tls' => true,
    ],
];
