<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\ReportOrder;
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

        $reportOrders = ReportOrder::query()
            ->with(['screeningPackage', 'orderedBy', 'assignedTo'])
            ->forOrganization($organization)
            ->when(! $canViewAll, fn ($query) => $query->where('ordered_by_user_id', $request->user()->id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('report-orders.index', [
            'organization' => $organization,
            'reportOrders' => $reportOrders,
            'canCreate' => $request->user()?->hasPermission(Permission::OrgOrdersCreate, $organization) ?? false,
        ]);
    }
}
