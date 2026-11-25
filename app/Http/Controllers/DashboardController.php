<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): Response
    {
        $page = $dashboard->dashboardFor($request->user());

        return Inertia::render($page['component'], $page['props']);
    }
}
