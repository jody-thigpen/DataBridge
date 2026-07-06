<?php

namespace App\Http\Controllers;

use App\Services\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportRequestController extends Controller
{
    public function create(OrganizationContext $organizationContext): View
    {
        return view('reports.requests.create', [
            'organization' => $organizationContext->current(),
        ]);
    }

    public function store(Request $request, OrganizationContext $organizationContext): RedirectResponse
    {
        $validated = $request->validate([
            'subject_name' => ['required', 'string', 'max:255'],
            'package' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // Report orders will be persisted once vendor integrations are wired.
        return redirect()
            ->route('reports.index')
            ->with('status', "Report request for {$validated['subject_name']} has been queued for processing.");
    }
}
