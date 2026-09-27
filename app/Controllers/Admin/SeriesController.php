<?php

declare(strict_types=1);

namespace Garage\Controllers\Admin;

use Garage\Core\Request;
use Garage\Core\Response;
use Garage\Core\Session;
use Garage\Models\Series;
use Garage\Support\Str;

final class SeriesController extends AdminController
{
    public function index(Request $request): Response
    {
        return $this->adminView('admin/series/index', [
            'title' => t('admin.series'),
            'section' => 'series',
            'seriesList' => Series::all(),
        ]);
    }

    public function store(Request $request): Response
    {
        if ($denied = $this->authorizeWrite($request, '/admin/series')) {
            return $denied;
        }
        $name = $this->name($request);
        if ($name === null || Series::findByName($name)) {
            Session::flash('error', t($name === null ? 'series.invalid_name' : 'series.exists'));
        } else {
            Series::create($name);
            Session::flash('success', t('series.created', ['name' => $name]));
        }

        return Response::redirect(url('/admin/series'));
    }

    public function update(Request $request, array $params): Response
    {
        $series = ($id = $this->id($params)) ? Series::find($id) : null;
        if (!$series) {
            return Response::notFound();
        }
        if ($denied = $this->authorizeWrite($request, '/admin/series')) {
            return $denied;
        }
        $name = $this->name($request);
        $other = $name !== null ? Series::findByName($name) : null;
        if ($name === null || ($other && (int) $other['id'] !== (int) $series['id'])) {
            Session::flash('error', t($name === null ? 'series.invalid_name' : 'series.exists'));
        } else {
            Series::update((int) $series['id'], $name);
            Session::flash('success', t('series.updated', ['name' => $name]));
        }

        return Response::redirect(url('/admin/series'));
    }

    public function destroy(Request $request, array $params): Response
    {
        $series = ($id = $this->id($params)) ? Series::find($id) : null;
        if (!$series) {
            return Response::notFound();
        }
        if ($denied = $this->authorizeWrite($request, '/admin/series')) {
            return $denied;
        }
        Series::delete((int) $series['id']);
        Session::flash('success', t('series.deleted', ['name' => $series['name']]));

        return Response::redirect(url('/admin/series'));
    }

    private function name(Request $request): ?string
    {
        $name = Str::clean($request->input('name', ''));

        return $name === '' || Str::length($name) > 80 ? null : $name;
    }
}
