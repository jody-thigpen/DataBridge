<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\ReportRequestStatus;
use App\Models\ReportRequest;
use App\Models\ScreeningPackage;
use App\Services\OrganizationContext;
use App\Services\SearchReviewPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportRequestController extends Controller
{
    public function create(OrganizationContext $organizationContext): View
    {
        $organization = $organizationContext->current();
        abort_unless(auth()->user()?->hasPermission(Permission::OrgOrdersCreate, $organization), 403);

        $packages = $organization->assignedActivePackages()
            ->orderBy('name')
            ->get();

        return view('reports.requests.create', [
            'organization' => $organization,
            'packages' => $packages,
        ]);
    }

    public function store(
        Request $request,
        OrganizationContext $organizationContext,
        SearchReviewPolicy $searchReviewPolicy,
    ): RedirectResponse {
        $organization = $organizationContext->current();
        abort_unless($request->user()?->hasPermission(Permission::OrgOrdersCreate, $organization), 403);

        $packageIds = $organization->assignedActivePackages()->pluck('screening_packages.id');

        $validated = $request->validate([
            'subject_name' => ['required', 'string', 'max:255'],
            'screening_package_id' => ['required', Rule::in($packageIds)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $package = ScreeningPackage::query()->findOrFail($validated['screening_package_id']);
        $requiresReview = $searchReviewPolicy->packageRequiresReview($package, $organization);

        $reportRequest = ReportRequest::query()->create([
            'organization_id' => $organization->id,
            'screening_package_id' => $package->id,
            'requested_by_user_id' => $request->user()->id,
            'subject_name' => $validated['subject_name'],
            'notes' => $validated['notes'] ?? null,
            'price' => $package->priceForOrganization($organization),
            'requires_review' => $requiresReview,
            'status' => $requiresReview ? ReportRequestStatus::PendingReview : ReportRequestStatus::Submitted,
            'submitted_at' => $requiresReview ? null : now(),
        ]);

        $message = $requiresReview
            ? "Report request for {$validated['subject_name']} was submitted and is awaiting Saffhire review."
            : "Report request for {$validated['subject_name']} was submitted for processing.";

        return redirect()
            ->route('reports.index')
            ->with('status', $message);
    }
}
