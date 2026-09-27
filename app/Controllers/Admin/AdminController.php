<?php

declare(strict_types=1);

namespace Garage\Controllers\Admin;

use Garage\Controllers\Controller;
use Garage\Core\Auth;
use Garage\Core\Csrf;
use Garage\Core\Request;
use Garage\Core\Response;
use Garage\Core\Session;
use Garage\Core\View;

/** Base for the public-but-read-only admin panel (exhibition mode). */
abstract class AdminController extends Controller
{
    protected function adminView(string $template, array $data = [], int $status = 200): Response
    {
        $data += [
            'isOwner' => Auth::check(),
            'flash'   => Session::pullFlash(),
        ];

        return Response::html(View::render($template, $data, 'layouts/admin'), $status)->noIndex();
    }

    /** Reject the request early when the CSRF token is missing or wrong. */
    protected function rejectInvalidCsrf(Request $request): ?Response
    {
        if (Csrf::verify($request)) {
            return null;
        }
        Session::flash('error', t('csrf.invalid'));

        return Response::redirect(url('/admin'));
    }
}
