<?php

declare(strict_types=1);

/**
 * One-time installer for shared hosting without SSH.
 *
 * Open https://samueltorres.dev/garage/setup.php, type the panel password and it
 * creates the runtime folders (uploads/ with its no-PHP .htaccess), creates the
 * SQLite database (storage/garage.sqlite) and runs every pending migration. On success it writes storage/setup.lock and deletes itself;
 * while the lock exists the page answers 404, even if a later deploy brings it back.
 *
 * - Needs app/config.php (uploaded by hand) with the password_hash of the panel
 *   password. There is no database password: SQLite is a file.
 * - Max 5 wrong passwords per IP every 15 minutes (file based: the login_attempts
 *   table does not exist yet).
 */

use Garage\Core\ClientIp;
use Garage\Core\Config;
use Garage\Core\Database;
use Garage\Core\Migrator;
use Garage\Support\Installer;

const SETUP_MAX_ATTEMPTS = 5;
const SETUP_WINDOW = 900;

$lock = __DIR__ . '/storage/setup.lock';
$nonce = base64_encode(random_bytes(16));

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'none'; style-src 'nonce-{$nonce}'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");

if (is_file($lock)) {
    http_response_code(404);
    exit('Not found.');
}

if (!is_file(getenv('GARAGE_CONFIG') ?: __DIR__ . '/app/config.php')) {
    render($nonce, 'Falta app/config.php', '<p>Sube primero <code>app/config.php</code> a <code>public_html/garage/app/</code> con el File Manager y recarga esta página.</p>');
}

require __DIR__ . '/app/bootstrap.php';

$hash = (string) Config::get('admin.password_hash', '');
if ($hash === '') {
    render($nonce, 'Configuración incompleta', '<p><code>app/config.php</code> no tiene el hash de la contraseña del panel.</p>');
}

$problems = [];
if (!extension_loaded('pdo_sqlite')) {
    $problems[] = 'Activa la extensión <code>pdo_sqlite</code> en cPanel → Select PHP Version → Extensions.';
}
if (!is_writable(__DIR__ . '/storage')) {
    $problems[] = 'La carpeta <code>garage/storage/</code> no tiene permiso de escritura: ponle 755 en el File Manager.';
}
if ($problems) {
    render($nonce, 'Falta un paso en el servidor', '<ul><li>' . implode('</li><li>', $problems) . '</li></ul><p>Corrígelo y recarga esta página.</p>');
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $ipKey = hash_hmac('sha256', ClientIp::resolve($_SERVER), (string) Config::get('app.key', ''));
    $attemptsFile = __DIR__ . '/storage/cache/setup-attempts.json';
    $attempts = recentAttempts($attemptsFile, $ipKey);

    if (count($attempts) >= SETUP_MAX_ATTEMPTS) {
        http_response_code(429);
        $error = 'Demasiados intentos. Espera 15 minutos.';
    } elseif (!password_verify((string) ($_POST['password'] ?? ''), $hash)) {
        saveAttempt($attemptsFile, $ipKey);
        http_response_code(403);
        $error = 'Contraseña incorrecta.';
    } else {
        try {
            $installed = Installer::ensureRuntimeFolders();
            $result = (new Migrator(Database::pdo(), GARAGE_ROOT . '/migrations'))->run();
        } catch (Throwable $e) {
            error_log('GARAGE setup: ' . $e->getMessage());
            http_response_code(500);
            render($nonce, 'No se pudo instalar', '<p>' . e($e->getMessage()) . '</p><p>Revisa que <code>garage/storage/</code> tenga permiso de escritura y vuelve a intentarlo.</p>');
        }

        $locked = @file_put_contents($lock, date('c') . "\n", LOCK_EX) !== false;
        @unlink($attemptsFile);
        $deleted = @unlink(__FILE__);

        $items = '';
        foreach ($installed as $item) {
            $items .= '<li>Instalado ' . e($item) . '</li>';
        }
        foreach ($result['applied'] as $version) {
            $items .= '<li>Migración ' . e($version) . '</li>';
        }
        $body = '<p>Listo: ' . count($result['applied']) . ' migraciones aplicadas, ' . (int) $result['skipped'] . ' ya estaban.</p>'
            . ($items !== '' ? '<ul>' . $items . '</ul>' : '')
            . '<p>' . match (true) {
                $deleted => 'Este instalador se borró solo.',
                $locked => 'El instalador quedó desactivado. Si quieres, borra <code>garage/setup.php</code> desde el File Manager.',
                default => '<strong>No pude desactivar el instalador:</strong> borra <code>garage/setup.php</code> desde el File Manager.',
            } . '</p>'
            . '<p><a href="' . e(url('/admin')) . '">Ir al panel de control →</a></p>';
        render($nonce, 'GARAGE instalado', $body);
    }
}

render($nonce, 'Instalar GARAGE', ($error !== '' ? '<p class="error">' . e($error) . '</p>' : '')
    . '<p>Crea las carpetas y la base de datos SQLite con sus tablas. Solo funciona una vez.</p>'
    . '<form method="post" autocomplete="off">'
    . '<label for="password">Contraseña del panel</label>'
    . '<input id="password" name="password" type="password" required autofocus>'
    . '<button type="submit">Instalar</button>'
    . '</form>');

/** @return list<int> timestamps of this IP's failed attempts inside the window */
function recentAttempts(string $file, string $ipKey): array
{
    $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
    $since = time() - SETUP_WINDOW;

    return array_values(array_filter((array) ($data[$ipKey] ?? []), static fn ($t) => (int) $t > $since));
}

function saveAttempt(string $file, string $ipKey): void
{
    $data = is_file($file) ? (array) json_decode((string) file_get_contents($file), true) : [];
    $data[$ipKey] = [...recentAttempts($file, $ipKey), time()];
    if (!is_dir(dirname($file))) {
        @mkdir(dirname($file), 0755, true);
    }
    file_put_contents($file, json_encode($data), LOCK_EX);
}

function render(string $nonce, string $title, string $body): never
{
    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>{$title} · GARAGE</title>
<style nonce="{$nonce}">
  body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; box-sizing: border-box;
         background: #FFF3E3; color: #141414; font: 16px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; }
  main { width: 100%; max-width: 420px; background: #fff; border: 3px solid #141414; border-radius: 14px;
         box-shadow: 6px 6px 0 #141414; padding: 28px; box-sizing: border-box; }
  h1 { margin: 0 0 12px; font-size: 1.6rem; text-transform: uppercase; letter-spacing: .02em; }
  label { display: block; font-weight: 700; margin: 16px 0 6px; }
  input { width: 100%; box-sizing: border-box; padding: 12px; font: inherit; border: 2px solid #141414; border-radius: 8px; }
  button { margin-top: 16px; width: 100%; padding: 12px; font: inherit; font-weight: 800; text-transform: uppercase;
           color: #fff; background: #DD0200; border: 2px solid #141414; border-radius: 8px; cursor: pointer; }
  button:focus-visible, input:focus-visible, a:focus-visible { outline: 3px solid #1F4BFF; outline-offset: 2px; }
  .error { color: #DD0200; font-weight: 700; }
  code { background: #FFF3E3; padding: 0 4px; border-radius: 4px; }
  a { color: #1F4BFF; font-weight: 700; }
</style>
</head>
<body><main><h1>{$title}</h1>{$body}</main></body>
</html>
HTML;
    exit;
}
