<?php

namespace PiShift\Content\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PiShift\Content\Admin\Dashboard;

class DashboardController
{
    public function __invoke(Request $request, Dashboard $dashboard): Response
    {
        return Inertia::render('Dashboard', [
            'widgets' => $dashboard->forUser($request->user()),
            'urls' => [
                'pages' => $request->user()->can('content.manage') ? route('content.admin.pages.index') : null,
            ],
        ]);
    }
}
