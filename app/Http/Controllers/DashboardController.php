<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\ReportRequest;
use App\Services\ImpersonationService;
use App\Services\OrganizationContext;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(OrganizationContext $organizationContext, ImpersonationService $impersonation): View
    {
        $user = auth()->user();
        $canViewReportRequestQueue = $user->hasPermission(Permission::PlatformReportRequestsView);

        return view('dashboard', [
            'user' => $user,
            'organization' => $organizationContext->current(),
            'isPlatformView' => $user->isPlatformUser() && ! $impersonation->isImpersonating(),
            'canViewReportRequestQueue' => $canViewReportRequestQueue,
            'pendingReviewCount' => $canViewReportRequestQueue
                ? ReportRequest::query()->awaitingReviewBy($user)->count()
                : 0,
        ]);
    }
}
