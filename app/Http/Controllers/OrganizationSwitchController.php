<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationSwitchController extends Controller
{
    public function update(Request $request, OrganizationContext $organizationContext): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
        ]);

        $organization = Organization::query()->findOrFail($validated['organization_id']);
        $organizationContext->set($organization);

        return back()->with('status', "Switched to {$organization->name}.");
    }

    public function destroy(OrganizationContext $organizationContext): RedirectResponse
    {
        $organizationContext->exitPlatformView();

        return redirect()
            ->route('platform.clients.index')
            ->with('status', 'Returned to platform view.');
    }
}
