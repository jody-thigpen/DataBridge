<?php

namespace App\Services;

use App\Enums\ReportRequestStatus;
use App\Models\Organization;
use App\Models\ReportRequest;
use App\Models\ScreeningPackage;
use Illuminate\Support\Str;

class CandidateInviteService
{
    public function __construct(
        private readonly SearchReviewPolicy $searchReviewPolicy,
    ) {}

    public function issueToken(ReportRequest $reportRequest): string
    {
        $token = Str::random(64);

        $reportRequest->forceFill([
            'invite_token' => $token,
            'invite_sent_at' => now(),
            'candidate_opened_at' => null,
        ])->save();

        return $token;
    }

    public function inviteTtlDays(): int
    {
        return max(1, (int) config('candidate_intake.invite_ttl_days', 3));
    }

    public function assertInviteActive(ReportRequest $reportRequest): void
    {
        if ($reportRequest->isInviteExpired()) {
            abort(410, 'This intake link has expired. Please ask the requesting organization to resend your invitation.');
        }
    }

    public function markOpened(ReportRequest $reportRequest): void
    {
        if ($reportRequest->candidate_opened_at !== null) {
            return;
        }

        $reportRequest->forceFill(['candidate_opened_at' => now()])->save();
    }

    /**
     * @param  array<string, mixed>  $answers
     * @param  list<int>  $acknowledgedDocumentIds
     */
    public function complete(
        ReportRequest $reportRequest,
        array $answers,
        array $acknowledgedDocumentIds,
        string $ipAddress,
        ?string $userAgent,
    ): void {
        $organization = $reportRequest->organization;
        $package = $reportRequest->screeningPackage;

        abort_unless($organization instanceof Organization && $package instanceof ScreeningPackage, 404);

        $requiresReview = $this->searchReviewPolicy->packageRequiresReview($package, $organization);
        $organization->loadMissing('clientManager');

        $assignedToUserId = null;
        $assignedAt = null;
        $status = $requiresReview ? ReportRequestStatus::PendingReview : ReportRequestStatus::Submitted;

        if ($requiresReview && $organization->client_manager_id !== null) {
            $assignedToUserId = $organization->client_manager_id;
            $assignedAt = now();
            $status = ReportRequestStatus::Assigned;
        }

        $reportRequest->forceFill([
            'candidate_answers' => $answers,
            'acknowledged_document_ids' => $acknowledgedDocumentIds,
            'candidate_completed_at' => now(),
            'authorization_accepted_at' => now(),
            'authorization_ip' => $ipAddress,
            'authorization_user_agent' => $userAgent,
            'requires_review' => $requiresReview,
            'status' => $status,
            'assigned_to_user_id' => $assignedToUserId,
            'assigned_at' => $assignedAt,
            'submitted_at' => $requiresReview ? null : now(),
            'invite_token' => null,
        ])->save();
    }
}
