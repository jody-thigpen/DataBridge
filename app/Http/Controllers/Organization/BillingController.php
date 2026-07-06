<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Services\OrganizationContext;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function edit(OrganizationContext $organizationContext): View
    {
        return view('organization.billing.edit', [
            'organization' => $organizationContext->current(),
        ]);
    }
}
