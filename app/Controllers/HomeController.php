<?php

declare(strict_types=1);

namespace Garage\Controllers;

use Garage\Core\Request;
use Garage\Core\Response;
use Garage\Core\View;
use Garage\Models\Car;
use Garage\Services\Catalog;

/** Gallery: 3D intro + filterable grid. ?partial=1 returns only the cards (infinite scroll). */
final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = Catalog::filters($request->queryAll());
        $result = Catalog::search($filters, (int) $request->query('page', 1));
        $query = Catalog::queryString($filters);
        $this->alternates('/' . $query, '/' . $query);

        $nextUrl = $result['page'] < $result['pages']
            ? url('/') . Catalog::queryString($filters, ['page' => $result['page'] + 1])
            : null;

        if ($request->query('partial') === '1') {
            return Response::json([
                'html' => View::partial('public/_cards', ['cars' => $result['cars']]),
                'next' => $nextUrl,
                'total' => $result['total'],
            ]);
        }

        $filtered = Catalog::isFiltered($filters);

        return $this->view('public/home', [
            'title' => 'GARAGE — ' . t('home.kicker') . ' · Samuel Torres',
            'filters' => $filters,
            'result' => $result,
            'nextUrl' => $nextUrl,
            'brands' => Catalog::brandsInUse(),
            'seriesList' => Catalog::seriesInUse(),
            'rarities' => Car::RARITIES,
            'conditions' => Car::CONDITIONS,
            'filtered' => $filtered,
            // The intro plays only on the unfiltered first page (and once per session, see intro.js).
            'introCars' => !$filtered && $result['page'] === 1 ? Catalog::introCars() : [],
        ]);
    }
}
