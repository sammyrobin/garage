<?php

declare(strict_types=1);

namespace Garage\Controllers;

use Garage\Core\Response;
use Garage\Core\View;
use Garage\Services\Stats;

abstract class Controller
{
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        try {
            $ribbon = Stats::ribbon();
        } catch (\Throwable) {
            $ribbon = null;   // error pages must render even without the database
        }

        return Response::html(View::render($template, $data + ['ribbon' => $ribbon], 'layouts/public'), $status);
    }

    /** Per-language paths of the current page, for hreflang and the language switch. */
    protected function alternates(string $es, string $en): void
    {
        View::share('alternates', ['es' => $es, 'en' => $en]);
    }
}
