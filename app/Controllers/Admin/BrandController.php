<?php

declare(strict_types=1);

namespace Garage\Controllers\Admin;

use Garage\Core\Request;
use Garage\Core\Response;
use Garage\Core\Session;
use Garage\Models\Brand;
use Garage\Support\Str;

final class BrandController extends AdminController
{
    public function index(Request $request): Response
    {
        return $this->adminView('admin/brands/index', [
            'title' => t('admin.brands'),
            'section' => 'brands',
            'brands' => Brand::all(),
        ]);
    }

    public function store(Request $request): Response
    {
        if ($denied = $this->authorizeWrite($request, '/admin/brands')) {
            return $denied;
        }
        [$name, $color, $error] = $this->input($request);
        if ($error === null && Brand::findByName($name)) {
            $error = t('brand.exists');
        }
        if ($error !== null) {
            Session::flash('error', $error);
        } else {
            Brand::create($name, $color);
            Session::flash('success', t('brand.created', ['name' => $name]));
        }

        return Response::redirect(url('/admin/brands'));
    }

    public function update(Request $request, array $params): Response
    {
        $brand = ($id = $this->id($params)) ? Brand::find($id) : null;
        if (!$brand) {
            return Response::notFound();
        }
        if ($denied = $this->authorizeWrite($request, '/admin/brands')) {
            return $denied;
        }
        [$name, $color, $error] = $this->input($request);
        $other = $error === null ? Brand::findByName($name) : null;
        if ($other && (int) $other['id'] !== (int) $brand['id']) {
            $error = t('brand.exists');
        }
        if ($error !== null) {
            Session::flash('error', $error);
        } else {
            Brand::update((int) $brand['id'], $name, $color);
            Session::flash('success', t('brand.updated', ['name' => $name]));
        }

        return Response::redirect(url('/admin/brands'));
    }

    public function destroy(Request $request, array $params): Response
    {
        $brand = ($id = $this->id($params)) ? Brand::find($id) : null;
        if (!$brand) {
            return Response::notFound();
        }
        if ($denied = $this->authorizeWrite($request, '/admin/brands')) {
            return $denied;
        }
        if (Brand::delete((int) $brand['id'])) {
            Session::flash('success', t('brand.deleted', ['name' => $brand['name']]));
        } else {
            Session::flash('error', t('brand.in_use', ['name' => $brand['name']]));
        }

        return Response::redirect(url('/admin/brands'));
    }

    /** @return array{0: string, 1: string, 2: ?string} name, color, error */
    private function input(Request $request): array
    {
        $name = Str::clean($request->input('name', ''));
        $color = strtoupper(Str::clean($request->input('accent_color', '#141414')));
        $error = null;
        if ($name === '' || Str::length($name) > 80) {
            $error = t('brand.invalid_name');
        } elseif (!Brand::isValidColor($color)) {
            $error = t('brand.invalid_color');
        }

        return [$name, $color, $error];
    }
}
