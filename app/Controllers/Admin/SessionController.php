<?php

declare(strict_types=1);

namespace Garage\Controllers\Admin;

use Garage\Core\Auth;
use Garage\Core\Request;
use Garage\Core\Response;
use Garage\Core\Session;

/** Start / end the 30-minute owner session from the exhibition banner. */
final class SessionController extends AdminController
{
    public function store(Request $request): Response
    {
        if ($rejected = $this->rejectInvalidCsrf($request)) {
            return $rejected;
        }

        $result = Auth::attempt((string) $request->input('password', ''), $request->ip());
        if ($result->passed()) {
            Session::flash('success', t('auth.welcome'));
        } else {
            Session::flash('error', $result->message());
        }

        return Response::redirect(url('/admin'));
    }

    public function destroy(Request $request): Response
    {
        if ($rejected = $this->rejectInvalidCsrf($request)) {
            return $rejected;
        }

        Auth::logout();
        Session::flash('success', t('auth.logged_out'));

        return Response::redirect(url('/admin'));
    }
}
