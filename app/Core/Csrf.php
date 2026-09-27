<?php

declare(strict_types=1);

namespace Garage\Core;

/** Per-session synchronizer token. Every admin form posts it as "_csrf". */
final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf', $token);
        }

        return $token;
    }

    public static function verify(Request $request): bool
    {
        $sent = $request->input('_csrf') ?? $request->header('X-CSRF-Token');
        $token = Session::get('_csrf');

        return is_string($sent) && is_string($token) && hash_equals($token, $sent);
    }
}
