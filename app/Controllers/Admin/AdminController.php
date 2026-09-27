<?php

declare(strict_types=1);

namespace Garage\Controllers\Admin;

use Garage\Controllers\Controller;
use Garage\Core\Auth;
use Garage\Core\AuthResult;
use Garage\Core\Csrf;
use Garage\Core\Deferred;
use Garage\Core\Request;
use Garage\Core\Response;
use Garage\Core\Session;
use Garage\Core\View;
use Garage\Services\NotificationService;

/** Base for the public-but-read-only admin panel (exhibition mode). */
abstract class AdminController extends Controller
{
    protected function adminView(string $template, array $data = [], int $status = 200): Response
    {
        $data += [
            'isOwner' => Auth::check(),
            'flash'   => Session::pullFlash(),
            'section' => '',
        ];

        // Send e-mails left pending (group window closed) without delaying this page.
        Deferred::add([NotificationService::class, 'flushDue']);

        return Response::html(View::render($template, $data, 'layouts/admin'), $status)->noIndex();
    }

    /** Reject the request early when the CSRF token is missing or wrong. */
    protected function rejectInvalidCsrf(Request $request, string $back = '/admin'): ?Response
    {
        if (Csrf::verify($request)) {
            return null;
        }
        if ($this->wantsJson($request)) {
            return Response::json(['ok' => false, 'message' => t('csrf.invalid')], 419);
        }
        Session::flash('error', t('csrf.invalid'));

        return Response::redirect(url($back));
    }

    /**
     * Gate for every write: CSRF, then owner session or password.
     * Runs before any uploaded file or input is processed.
     */
    protected function authorizeWrite(Request $request, string $back): ?Response
    {
        if ($rejected = $this->rejectInvalidCsrf($request, $back)) {
            return $rejected;
        }

        $result = Auth::authorize($request);
        if ($result->passed()) {
            return null;
        }

        return $this->denied($request, $result, $back);
    }

    protected function denied(Request $request, AuthResult $result, string $back): Response
    {
        $status = $result->status === AuthResult::LOCKED ? 429 : 403;
        if ($this->wantsJson($request)) {
            return Response::json(['ok' => false, 'message' => $result->message(), 'errors' => ['password' => $result->message()]], $status);
        }
        Session::flash('error', $result->message());

        return Response::redirect(url($back));
    }

    protected function wantsJson(Request $request): bool
    {
        return str_contains((string) $request->header('Accept'), 'application/json');
    }

    /** Positive integer route parameter or null. */
    protected function id(array $params): ?int
    {
        $id = $params['id'] ?? '';

        return ctype_digit($id) && (int) $id > 0 ? (int) $id : null;
    }
}
