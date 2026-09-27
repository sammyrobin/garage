<?php

declare(strict_types=1);

namespace Garage\Controllers\Admin;

use Garage\Core\Auth;
use Garage\Core\Database;
use Garage\Core\Request;
use Garage\Core\Response;

final class DashboardController extends AdminController
{
    public function index(Request $request): Response
    {
        $totals = Database::query(
            'SELECT COUNT(*) AS cars,
                    COUNT(DISTINCT brand_id) AS brands,
                    SUM(rarity = \'super_treasure_hunt\') AS sths,
                    SUM(cost_mxn) AS invested,
                    AVG(cost_mxn) AS average
               FROM cars'
        )->fetch();

        // Private figures never leave the server without an owner session.
        if (!Auth::check()) {
            $totals['invested'] = null;
            $totals['average'] = null;
        }

        return $this->adminView('admin/dashboard', [
            'title'  => t('admin.dashboard'),
            'totals' => $totals,
        ]);
    }
}
