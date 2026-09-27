<?php

declare(strict_types=1);

namespace Garage\Core;

/** Hardened PHP session, started only when a page needs it (admin panel). */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) max(1800, (int) Config::get('admin.session_ttl', 1800)));

        session_name('garage_sid');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => rtrim((string) Config::get('app.base_path', ''), '/') . '/',
            'secure'   => (bool) Config::get('session.secure', true),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    /** True if a session is active or the browser sent a session cookie. */
    public static function exists(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE || isset($_COOKIE['garage_sid']);
    }

    /** New session ID (prevents session fixation when privileges change). */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();

        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /** One-time message shown on the next page. */
    public static function flash(string $type, string $message): void
    {
        self::set('_flash', ['type' => $type, 'message' => $message]);
    }

    public static function pullFlash(): ?array
    {
        $flash = self::get('_flash');
        self::forget('_flash');

        return $flash;
    }
}
