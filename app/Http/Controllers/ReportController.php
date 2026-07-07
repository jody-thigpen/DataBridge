<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\ReportRequest;
use App\Services\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, OrganizationContext $organizationContext): View
    {
        $organization = $organizationContext->current();
        abort_unless(
            $request->user()?->hasPermission(Permission::OrgOrdersView, $organization)
            || $request->user()?->hasPermission(Permission::OrgOrdersViewAll, $organization),
            403,
        );

        $canViewAll = $request->user()?->hasPermission(Permission::OrgOrdersViewAll, $organization) ?? false;

        $reportRequests = ReportRequest::query()
            ->with(['screeningPackage', 'requestedBy', 'assignedTo'])
            ->forOrganization($organization)
            ->when(! $canViewAll, fn ($query) => $query->where('requested_by_user_id', $request->user()->id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('reports.index', [
            'organization' => $organization,
            'reportRequests' => $reportRequests,
            'canCreate' => $request->user()?->hasPermission(Permission::OrgOrdersCreate, $organization) ?? false,
        ]);
    }
}
