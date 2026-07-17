<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\ReportRequestStatus;
use App\Mail\CandidateIntakeInvitation;
use App\Models\ReportRequest;
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

        $reportRequest = ReportRequest::query()->create([
            'organization_id' => $organization->id,
            'screening_package_id' => $package->id,
            'requested_by_user_id' => $request->user()->id,
            'subject_name' => $validated['subject_name'],
            'candidate_email' => $validated['candidate_email'],
            'candidate_phone' => $validated['candidate_phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'price' => $package->priceForOrganization($organization),
            'requires_review' => $requiresReview,
            'status' => ReportRequestStatus::AwaitingCandidate,
        ]);

        $token = $inviteService->issueToken($reportRequest);
        $inviteUrl = route('candidate.intake.show', $token);

        Mail::to($reportRequest->candidate_email)->send(
            new CandidateIntakeInvitation($reportRequest->load('organization'), $inviteUrl),
        );

        return redirect()
            ->route('reports.index')
            ->with('status', "Report request for {$validated['subject_name']} was created. An intake invitation was emailed to {$validated['candidate_email']}.");
    }

    public function resendInvite(
        Request $request,
        ReportRequest $reportRequest,
        OrganizationContext $organizationContext,
        CandidateInviteService $inviteService,
    ): RedirectResponse {
        $organization = $organizationContext->current();
        abort_unless($request->user()?->hasPermission(Permission::OrgOrdersCreate, $organization), 403);
        abort_unless($reportRequest->organization_id === $organization->id, 404);
        abort_unless($reportRequest->isAwaitingCandidate(), 422, 'This request is no longer awaiting candidate intake.');
        abort_unless(filled($reportRequest->candidate_email), 422);

        $token = $inviteService->issueToken($reportRequest);
        $inviteUrl = route('candidate.intake.show', $token);

        Mail::to($reportRequest->candidate_email)->send(
            new CandidateIntakeInvitation($reportRequest->load('organization'), $inviteUrl),
        );

        return back()->with('status', "Intake invitation resent to {$reportRequest->candidate_email}. The new link is valid for {$inviteService->inviteTtlDays()} days.");
    }
}
