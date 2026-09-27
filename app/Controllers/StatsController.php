<?php

declare(strict_types=1);

namespace Garage\Controllers;

use Garage\Core\Auth;
use Garage\Core\Request;
use Garage\Core\Response;
use Garage\Services\Stats;

/** /estadisticas · /en/stats. Total invested and average cost only for the owner. */
final class StatsController extends Controller
{
    public function index(Request $request): Response
    {
        $stats = Stats::collection();
        $isOwner = Auth::check();
        if (!$isOwner) {
            unset($stats['invested'], $stats['average']);
        }

        $this->alternates(route_path('stats', [], 'es'), route_path('stats', [], 'en'));

        return $this->view('public/stats', [
            'title' => t('stats.title') . ' · GARAGE',
            'stats' => $stats,
            'isOwner' => $isOwner,
        ]);
    }
}
