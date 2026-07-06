<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Services\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(OrganizationContext $organizationContext): View
    {
        return view('organization.profile.edit', [
            'organization' => $organizationContext->current(),
        ]);
    }

    public function update(Request $request, OrganizationContext $organizationContext): RedirectResponse
    {
        $organization = $organizationContext->current();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $organization->update($validated);

        return back()->with('status', 'Organization profile updated.');
    }
}
