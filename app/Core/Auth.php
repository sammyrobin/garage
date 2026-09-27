<?php

declare(strict_types=1);

namespace Garage\Core;

/**
 * Exhibition-mode authentication.
 *
 * The admin panel is public to browse. Any write needs either an active owner
 * session (30 min of inactivity) or the owner password sent with the form.
 * Only the password_hash lives in app/config.php; there is no users table.
 */
final class Auth
{
    private const SESSION_KEY = '_owner';

    /** Hash of a discarded random string: keeps timing equal when no hash is configured. */
    private const DUMMY_HASH = '$2y$12$kabl70ozh.HwDDrELZPSyumx.srNvyt6NwXnVrhmUx3f0ToIkuhB.';

    public static function check(): bool
    {
        $owner = Session::get(self::SESSION_KEY);
        if (!is_array($owner)) {
            return false;
        }

        if (time() - (int) ($owner['last_seen'] ?? 0) > (int) Config::get('admin.session_ttl', 1800)) {
            Session::forget(self::SESSION_KEY);
            return false;
        }

        $owner['last_seen'] = time();
        Session::set(self::SESSION_KEY, $owner);

        return true;
    }

    /**
     * Allow a write: active session, or a correct password in the "password" field.
     * Must run BEFORE touching any uploaded file.
     */
    public static function authorize(Request $request): AuthResult
    {
        if (self::check()) {
            return AuthResult::ok();
        }

        return self::attempt((string) $request->input('password', ''), $request->ip());
    }

    public static function attempt(string $password, string $ip): AuthResult
    {
        $limiter = RateLimiter::forOwnerPassword();

        $lockedFor = $limiter->lockedFor($ip);
        if ($lockedFor > 0) {
            Logger::warning('Owner password blocked (rate limit)', ['ip' => ClientIp::mask($ip)]);
            return AuthResult::locked($lockedFor);
        }

        if ($password === '') {
            return AuthResult::missing();
        }

        $hash = (string) Config::get('admin.password_hash', '');
        if ($hash === '') {
            Logger::error('admin.password_hash is not configured');
        }

        if (!password_verify($password, $hash !== '' ? $hash : self::DUMMY_HASH) || $hash === '') {
            $limiter->hit($ip);
            Logger::warning('Failed owner password', ['ip' => ClientIp::mask($ip)]);

            $lockedFor = $limiter->lockedFor($ip);
            return $lockedFor > 0 ? AuthResult::locked($lockedFor) : AuthResult::invalid();
        }

        $limiter->clear($ip);
        Session::regenerate();
        Session::set(self::SESSION_KEY, ['since' => time(), 'last_seen' => time()]);
        Logger::info('Owner session started', ['ip' => ClientIp::mask($ip)]);

        return AuthResult::ok();
    }

    public static function logout(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::regenerate();
    }
}
