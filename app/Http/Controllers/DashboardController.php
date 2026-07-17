<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\ReportOrder;
use App\Services\ImpersonationService;
use App\Services\OrganizationContext;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(OrganizationContext $organizationContext, ImpersonationService $impersonation): View
    {
        $user = auth()->user();
        $canViewReportOrderQueue = $user->hasPermission(Permission::PlatformReportOrdersView);

        return view('dashboard', [
            'user' => $user,
            'organization' => $organizationContext->current(),
            'isPlatformView' => $user->isPlatformUser() && ! $impersonation->isImpersonating(),
            'canViewReportOrderQueue' => $canViewReportOrderQueue,
            'pendingReviewCount' => $canViewReportOrderQueue
                ? ReportOrder::query()->awaitingReviewBy($user)->count()
                : 0,
        ]);
    }
}
