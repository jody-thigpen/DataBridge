<?php

namespace App\Http\Middleware;

use App\Services\ImpersonationService;
use App\Services\NavigationMenu;
use App\Services\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetOrganizationContext
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly ImpersonationService $impersonation,
        private readonly NavigationMenu $navigationMenu,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            View::share('currentOrganization', $this->organizationContext->current());
            View::share('navigationItems', $this->navigationMenu->items());
            View::share('navigationSections', $this->navigationMenu->sections());
            View::share('isImpersonating', $this->impersonation->isImpersonating());
            View::share('impersonator', $this->impersonation->impersonator());
        }

        return $next($request);
    }
}
