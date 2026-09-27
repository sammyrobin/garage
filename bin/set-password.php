<?php

declare(strict_types=1);

/**
 * Store the owner password hash in the config file (app/config.php, or the file in
 * GARAGE_CONFIG: app/config.local.php under Docker).
 *   docker compose exec app php bin/set-password.php
 *
 * The password is read without echo and only its password_hash() is written.
 * Production: run it on a copy of app/config.php before uploading that file.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$configFile = getenv('GARAGE_CONFIG') ?: dirname(__DIR__) . '/app/config.php';
if (!is_file($configFile)) {
    fwrite(STDERR, basename($configFile) . " not found. Copy app/config.example.php first.\n");
    exit(1);
}

function prompt_hidden(string $label): string
{
    fwrite(STDOUT, $label);
    $canHide = DIRECTORY_SEPARATOR === '/' && stream_isatty(STDIN);
    if ($canHide) {
        shell_exec('stty -echo');
    }
    $value = rtrim((string) fgets(STDIN), "\r\n");
    if ($canHide) {
        shell_exec('stty echo');
    }
    fwrite(STDOUT, "\n");

    return $value;
}

$password = prompt_hidden('New owner password: ');
if (strlen($password) < 12) {
    fwrite(STDERR, "Use at least 12 characters.\n");
    exit(1);
}
if (!hash_equals($password, prompt_hidden('Repeat it: '))) {
    fwrite(STDERR, "Passwords do not match.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$source = (string) file_get_contents($configFile);
// Callback, not a replacement string: bcrypt hashes contain "$2y$", which
// preg_replace would treat as a back-reference.
$updated = preg_replace_callback(
    "/('password_hash'\s*=>\s*)'[^']*'/",
    static fn (array $m): string => $m[1] . var_export($hash, true),
    $source,
    1,
    $count
);

if ($count !== 1 || $updated === null) {
    fwrite(STDERR, "Could not find 'password_hash' in the config file.\n");
    exit(1);
}

file_put_contents($configFile, $updated, LOCK_EX);
echo "Owner password hash saved to " . basename($configFile) . ".\n";
