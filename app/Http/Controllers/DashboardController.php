<?php

namespace App\Http\Controllers;

use App\Services\ImpersonationService;
use App\Services\OrganizationContext;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(OrganizationContext $organizationContext, ImpersonationService $impersonation): View
    {
        $user = auth()->user();

        return view('dashboard', [
            'user' => $user,
            'organization' => $organizationContext->current(),
            'isPlatformView' => $user->isPlatformUser() && ! $impersonation->isImpersonating(),
        ]);
    }
}
