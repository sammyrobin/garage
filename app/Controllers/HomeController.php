<?php

declare(strict_types=1);

namespace Garage\Controllers;

use Garage\Core\Request;
use Garage\Core\Response;

/** Public home. Phase 1 placeholder; the gallery and 3D intro arrive in phases 3–4. */
final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('public/home', ['title' => 'GARAGE — by Samuel Torres']);
    }
}
