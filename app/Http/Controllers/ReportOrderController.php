<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\ReportOrderStatus;
use App\Mail\CandidateIntakeInvitation;
use App\Models\ReportOrder;
use App\Models\ScreeningPackage;
use App\Services\CandidateFormQuestionDefaults;
use App\Services\CandidateInviteService;
use App\Services\OrganizationContext;
use App\Services\SearchReviewPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportOrderController extends Controller
{
    public function create(OrganizationContext $organizationContext): View
    {
        $organization = $organizationContext->current();
        abort_unless(auth()->user()?->hasPermission(Permission::OrgOrdersCreate, $organization), 403);

        $packages = $organization->assignedActivePackages()
            ->orderBy('name')
            ->get();

        return view('report-orders.create', [
            'organization' => $organization,
            'packages' => $packages,
        ]);
    }

    public function store(
        Request $request,
        OrganizationContext $organizationContext,
        SearchReviewPolicy $searchReviewPolicy,
        CandidateInviteService $inviteService,
        CandidateFormQuestionDefaults $questionDefaults,
    ): RedirectResponse {
        $organization = $organizationContext->current();
        abort_unless($request->user()?->hasPermission(Permission::OrgOrdersCreate, $organization), 403);

        $packageIds = $organization->assignedActivePackages()->pluck('screening_packages.id');

        $validated = $request->validate([
            'subject_name' => ['required', 'string', 'max:255'],
            'candidate_email' => ['required', 'email', 'max:255'],
            'candidate_phone' => ['nullable', 'string', 'max:50'],
            'screening_package_id' => ['required', Rule::in($packageIds)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $package = ScreeningPackage::query()->findOrFail($validated['screening_package_id']);
        $requiresReview = $searchReviewPolicy->packageRequiresReview($package, $organization);

        if ($organization->candidateFormQuestions()->active()->doesntExist()) {
            $questionDefaults->seedForOrganization($organization);
        }

        $reportOrder = ReportOrder::query()->create([
            'organization_id' => $organization->id,
            'screening_package_id' => $package->id,
            'ordered_by_user_id' => $request->user()->id,
            'subject_name' => $validated['subject_name'],
            'candidate_email' => $validated['candidate_email'],
            'candidate_phone' => $validated['candidate_phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'price' => $package->priceForOrganization($organization),
            'requires_review' => $requiresReview,
            'status' => ReportOrderStatus::AwaitingCandidate,
        ]);

        $token = $inviteService->issueToken($reportOrder);
        $inviteUrl = route('candidate.intake.show', $token);

        Mail::to($reportOrder->candidate_email)->send(
            new CandidateIntakeInvitation($reportOrder->load('organization'), $inviteUrl),
        );

        return redirect()
            ->route('report-orders.index')
            ->with('status', "Report order for {$validated['subject_name']} was created. An intake invitation was emailed to {$validated['candidate_email']}.");
    }

    public function resendInvite(
        Request $request,
        ReportOrder $reportOrder,
        OrganizationContext $organizationContext,
        CandidateInviteService $inviteService,
    ): RedirectResponse {
        $organization = $organizationContext->current();
        abort_unless($request->user()?->hasPermission(Permission::OrgOrdersCreate, $organization), 403);
        abort_unless($reportOrder->organization_id === $organization->id, 404);
        abort_unless($reportOrder->isAwaitingCandidate(), 422, 'This request is no longer awaiting candidate intake.');
        abort_unless(filled($reportOrder->candidate_email), 422);

        $token = $inviteService->issueToken($reportOrder);
        $inviteUrl = route('candidate.intake.show', $token);

        Mail::to($reportOrder->candidate_email)->send(
            new CandidateIntakeInvitation($reportOrder->load('organization'), $inviteUrl),
        );

        return back()->with('status', "Intake invitation resent to {$reportOrder->candidate_email}. The new link is valid for {$inviteService->inviteTtlDays()} days.");
    }
}
