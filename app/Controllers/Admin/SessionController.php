<?php

declare(strict_types=1);

namespace Garage\Controllers\Admin;

use Garage\Core\Auth;
use Garage\Core\Request;
use Garage\Core\Response;
use Garage\Core\Session;

/**
 * Start / end the 30-minute owner session.
 * The car form calls store() as JSON first, so photos only leave the browser
 * once the password has been accepted.
 */
final class SessionController extends AdminController
{
    public function store(Request $request): Response
    {
        if ($rejected = $this->rejectInvalidCsrf($request)) {
            return $rejected;
        }

        $result = Auth::attempt((string) $request->input('password', ''), $request->ip());

        if ($this->wantsJson($request)) {
            return $result->passed()
                ? Response::json(['ok' => true])
                : $this->denied($request, $result, '/admin');
        }

        Session::flash($result->passed() ? 'success' : 'error', $result->passed() ? t('auth.welcome') : $result->message());

        return Response::redirect($this->safeBack($request));
    }

    public function destroy(Request $request): Response
    {
        if ($rejected = $this->rejectInvalidCsrf($request)) {
            return $rejected;
        }

        Auth::logout();
        Session::flash('success', t('auth.logged_out'));

        return Response::redirect($this->safeBack($request));
    }

    /** Return to the admin page the form was on (only same-app admin paths). */
    private function safeBack(Request $request): string
    {
        $back = (string) $request->input('back', '');

        return preg_match('#^/admin(/[a-z0-9/-]*)?$#', $back) ? url($back) : url('/admin');
    }
}
