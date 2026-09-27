<?php

declare(strict_types=1);

/**
 * One-time installer for shared hosting without SSH.
 *
 * Open https://samueltorres.dev/garage/setup.php and type the panel password:
 *
 * 1. If app/config.php does not exist, it writes it (mode 600): password_hash() of the
 *    typed password, a random app key and a random migrate token. The SMTP password
 *    is not copied: the config reads it at runtime from ../mail-config.php.
 * 2. Creates the runtime folders (uploads/ with its no-PHP .htaccess), the SQLite
 *    database (storage/garage.sqlite) and runs every pending migration.
 * 3. Writes storage/setup.lock and deletes itself; while the lock exists the page
 *    answers 404, even if a later deploy brings it back.
 *
 * Who may install: with an existing config.php, whoever knows the password it holds.
 * Without one, whoever knows the password behind app/setup-verifier.php (a
 * password_hash() committed to the private deploy repo only), so a stranger cannot
 * open the page first and choose the panel password. No verifier and no config:
 * nothing can be installed.
 *
 * Max 5 wrong passwords per IP every 15 minutes (file based: the login_attempts
 * table does not exist yet).
 */

use Garage\Core\ClientIp;
use Garage\Core\Config;
use Garage\Core\Database;
use Garage\Core\Migrator;
use Garage\Support\Installer;

const SETUP_MAX_ATTEMPTS = 5;
const SETUP_WINDOW = 900;

$lock = __DIR__ . '/storage/setup.lock';
$configFile = getenv('GARAGE_CONFIG') ?: __DIR__ . '/app/config.php';
$verifierFile = __DIR__ . '/app/setup-verifier.php';
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

require __DIR__ . '/app/Core/ClientIp.php';

// Which password unlocks the installer, and whether config.php still has to be written.
$createConfig = !is_file($configFile);
if ($createConfig) {
    $hash = is_file($verifierFile) ? (string) (require $verifierFile) : '';
    if ($hash === '') {
        render($nonce, 'Falta app/config.php', '<p>No existe <code>app/config.php</code> ni <code>app/setup-verifier.php</code>, así que el instalador no puede comprobar quién eres. Sube uno de los dos a <code>garage/app/</code> y recarga esta página.</p>');
    }
    $ipSecret = $hash;
} else {
    $config = require $configFile;
    $hash = (string) ($config['admin']['password_hash'] ?? '');
    if ($hash === '') {
        render($nonce, 'Configuración incompleta', '<p><code>app/config.php</code> no tiene el hash de la contraseña del panel.</p>');
    }
    $ipSecret = (string) ($config['app']['key'] ?? '');
}

$problems = [];
if (!extension_loaded('pdo_sqlite')) {
    $problems[] = 'Activa la extensión <code>pdo_sqlite</code> en cPanel → Select PHP Version → Extensions.';
}
if (!is_writable(__DIR__ . '/storage')) {
    $problems[] = 'La carpeta <code>garage/storage/</code> no tiene permiso de escritura: ponle 755 en el File Manager.';
}
if ($createConfig && !is_writable(dirname($configFile))) {
    $problems[] = 'La carpeta <code>garage/app/</code> no tiene permiso de escritura: ponle 755 en el File Manager.';
}
if ($problems) {
    render($nonce, 'Falta un paso en el servidor', '<ul><li>' . implode('</li><li>', $problems) . '</li></ul><p>Corrígelo y recarga esta página.</p>');
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $ipKey = hash_hmac('sha256', ClientIp::resolve($_SERVER), $ipSecret);
    $attemptsFile = __DIR__ . '/storage/cache/setup-attempts.json';
    $attempts = recentAttempts($attemptsFile, $ipKey);

    if (count($attempts) >= SETUP_MAX_ATTEMPTS) {
        http_response_code(429);
        $error = 'Demasiados intentos. Espera 15 minutos.';
    } elseif (!password_verify($password, $hash)) {
        saveAttempt($attemptsFile, $ipKey);
        http_response_code(403);
        $error = 'Contraseña incorrecta.';
    } else {
        $steps = [];
        try {
            if ($createConfig) {
                writeConfig($configFile, $password);
                $steps[] = 'Creado app/config.php (permisos 600)';
            }

            require __DIR__ . '/app/bootstrap.php';
            foreach (Installer::ensureRuntimeFolders() as $item) {
                $steps[] = 'Instalado ' . $item;
            }
            $result = (new Migrator(Database::pdo(), GARAGE_ROOT . '/migrations'))->run();
        } catch (Throwable $e) {
            error_log('GARAGE setup: ' . $e->getMessage());
            http_response_code(500);
            render($nonce, 'No se pudo instalar', '<p>' . esc($e->getMessage()) . '</p><p>Revisa que <code>garage/app/</code> y <code>garage/storage/</code> tengan permiso de escritura y vuelve a intentarlo.</p>');
        }

        $locked = @file_put_contents($lock, date('c') . "\n", LOCK_EX) !== false;
        @unlink($attemptsFile);
        if (is_file($verifierFile)) {
            @unlink($verifierFile);
        }
        $deleted = @unlink(__FILE__);

        foreach ($result['applied'] as $version) {
            $steps[] = 'Migración ' . $version;
        }
        $items = '';
        foreach ($steps as $step) {
            $items .= '<li>' . esc($step) . '</li>';
        }
        $body = '<p>Listo: ' . count($result['applied']) . ' migraciones aplicadas, ' . (int) $result['skipped'] . ' ya estaban.</p>'
            . ($items !== '' ? '<ul>' . $items . '</ul>' : '')
            . '<p>' . match (true) {
                $deleted => 'Este instalador se borró solo.',
                $locked => 'El instalador quedó desactivado. Si quieres, borra <code>garage/setup.php</code> desde el File Manager.',
                default => '<strong>No pude desactivar el instalador:</strong> borra <code>garage/setup.php</code> desde el File Manager.',
            } . '</p>'
            . '<p><a href="' . esc(url('/admin')) . '">Ir al panel de control →</a></p>';
        render($nonce, 'GARAGE instalado', $body);
    }
}

render($nonce, 'Instalar GARAGE', ($error !== '' ? '<p class="error">' . esc($error) . '</p>' : '')
    . '<p>' . ($createConfig
        ? 'Crea <code>app/config.php</code>, las carpetas y la base de datos SQLite con sus tablas.'
        : 'Crea las carpetas y la base de datos SQLite con sus tablas.')
    . ' Solo funciona una vez.</p>'
    . '<form method="post" autocomplete="off">'
    . '<label for="password">Contraseña del panel</label>'
    . '<input id="password" name="password" type="password" required autofocus>'
    . '<button type="submit">Instalar</button>'
    . '</form>');

/**
 * Write the production config from config.example.php: fresh hash and random secrets.
 * Written to a temp file and renamed, so a half-written config.php never exists.
 */
function writeConfig(string $file, string $password): void
{
    $config = require __DIR__ . '/app/config.example.php';
    $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/garage/setup.php'))), '/');
    $host = preg_replace('/[^A-Za-z0-9.\-:]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $secure = ClientIp::isSecure($_SERVER);

    $config['app']['env'] = 'production';
    $config['app']['url'] = ($secure ? 'https://' : 'http://') . $host . $basePath;
    $config['app']['base_path'] = $basePath;
    $config['app']['key'] = bin2hex(random_bytes(32));
    $config['admin']['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    $config['session']['secure'] = $secure;
    $config['migrate_token'] = bin2hex(random_bytes(32));
    $config['mail']['password'] = '__MAIL_PASSWORD__';

    // The SMTP password stays in the portfolio's mail-config.php (generated by its deploy).
    $mailPassword = <<<'PHP'
(static function (): string {
      $file = dirname(__DIR__, 2) . '/mail-config.php';
      $mail = is_file($file) ? require $file : [];

      return is_array($mail) ? (string) ($mail['password'] ?? '') : '';
    })()
PHP;
    $source = "<?php\n\n"
        . "// GARAGE production config, written by setup.php on " . gmdate('Y-m-d') . ". NOT in git.\n"
        . "// Contains the panel password HASH (not the password), the app key and the migrate token.\n"
        . "// SMTP password: read at runtime from public_html/mail-config.php.\n\n"
        . 'return ' . str_replace("'__MAIL_PASSWORD__'", $mailPassword, var_export($config, true)) . ";\n";

    $tmp = $file . '.tmp';
    if (@file_put_contents($tmp, $source, LOCK_EX) === false) {
        throw new RuntimeException('No pude escribir app/config.php.');
    }
    @chmod($tmp, 0600);
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        throw new RuntimeException('No pude escribir app/config.php.');
    }
    @chmod($file, 0600);
}

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

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function render(string $nonce, string $title, string $body): never
{
    $title = esc($title);
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
