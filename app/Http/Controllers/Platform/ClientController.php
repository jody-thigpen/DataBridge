<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $organizations = Organization::query()
            ->with('parent')
            ->withCount('users')
            ->orderBy('name')
            ->paginate(20);

        return view('platform.clients.index', compact('organizations'));
    }

    public function show(Organization $organization): View
    {
        $organization->load(['parent', 'children', 'roleAssignments.user', 'roleAssignments.role']);

        return view('platform.clients.show', compact('organization'));
    }

    public function enter(Organization $organization, OrganizationContext $organizationContext): RedirectResponse
    {
        $organizationContext->set($organization);

        return redirect()->route('dashboard')->with('status', "Now viewing {$organization->name}.");
    }
}
