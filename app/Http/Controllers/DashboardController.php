<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $service)
    {
        $summary = $service->getSummary();

        return view('dashboard', $summary);
    }
}
