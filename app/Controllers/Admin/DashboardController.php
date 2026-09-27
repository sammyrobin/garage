<?php

declare(strict_types=1);

namespace Garage\Controllers\Admin;

use Garage\Core\Auth;
use Garage\Core\Request;
use Garage\Core\Response;
use Garage\Models\Car;
use Garage\Models\CarPhoto;
use Garage\Services\DiskStatus;

final class DashboardController extends AdminController
{
    public function index(Request $request): Response
    {
        $isOwner = Auth::check();
        $totals = Car::totals();

        // Private figures never leave the server without an owner session.
        if (!$isOwner) {
            $totals['invested'] = null;
            $totals['average'] = null;
        }

        $recent = Car::paginate('', 1, 6)['rows'];

        return $this->adminView('admin/dashboard', [
            'title' => t('admin.dashboard'),
            'section' => 'dashboard',
            'totals' => $totals,
            'recent' => $recent,
            'photos' => CarPhoto::forCars(array_map(static fn (array $c): int => (int) $c['id'], $recent), ['front']),
            'quota' => $isOwner ? DiskStatus::quota() : null,
            'usage' => $isOwner ? DiskStatus::garageUsage() : null,
        ]);
    }
}
