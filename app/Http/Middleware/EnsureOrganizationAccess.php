<?php

namespace App\Http\Middleware;

use App\Services\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAccess
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->organizationContext->current() === null) {
            abort(403, 'Select a client organization to continue.');
        }

        return $next($request);
    }
}
