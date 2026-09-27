<?php

declare(strict_types=1);

namespace Garage\Controllers;

use Garage\Core\Response;
use Garage\Core\View;

abstract class Controller
{
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return Response::html(View::render($template, $data, 'layouts/public'), $status);
    }
}
