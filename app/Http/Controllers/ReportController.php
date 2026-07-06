<?php

namespace App\Http\Controllers;

use App\Services\OrganizationContext;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(OrganizationContext $organizationContext): View
    {
        return view('reports.index', [
            'organization' => $organizationContext->current(),
        ]);
    }
}
