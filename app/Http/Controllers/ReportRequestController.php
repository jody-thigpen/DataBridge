<?php

namespace App\Http\Controllers;

use App\Models\ScreeningPackage;
use App\Services\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportRequestController extends Controller
{
    public function create(OrganizationContext $organizationContext): View
    {
        $organization = $organizationContext->current();

        $packages = ScreeningPackage::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('reports.requests.create', [
            'organization' => $organization,
            'packages' => $packages,
        ]);
    }

    public function store(Request $request, OrganizationContext $organizationContext): RedirectResponse
    {
        $organization = $organizationContext->current();

        $packageIds = ScreeningPackage::query()
            ->where('is_active', true)
            ->pluck('id');

        $validated = $request->validate([
            'subject_name' => ['required', 'string', 'max:255'],
            'screening_package_id' => ['required', Rule::in($packageIds)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $package = ScreeningPackage::query()->findOrFail($validated['screening_package_id']);

        // Report orders will be persisted once vendor integrations are wired.
        return redirect()
            ->route('reports.index')
            ->with('status', "Report request for {$validated['subject_name']} ({$package->name}, {$package->formattedPriceForOrganization($organization)}) has been queued for processing.");
    }
}
