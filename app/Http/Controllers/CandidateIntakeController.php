<?php

namespace App\Http\Controllers;

use App\Enums\ReportRequestStatus;
use App\Models\ComplianceDocument;
use App\Models\ReportRequest;
use App\Services\CandidateIntakeValidator;
use App\Services\CandidateInviteService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateIntakeController extends Controller
{
    public function show(
        string $token,
        CandidateInviteService $inviteService,
        CandidateIntakeValidator $validator,
        TenantContext $tenantContext,
    ): View {
        $reportRequest = $this->findInvitedRequest($token, $tenantContext);

        if ($reportRequest->isInviteExpired()) {
            return view('candidate.expired', [
                'organization' => $reportRequest->organization,
                'ttlDays' => $inviteService->inviteTtlDays(),
            ]);
        }

        $inviteService->markOpened($reportRequest);

        $organization = $reportRequest->organization;
        $questions = $validator->activeQuestionsFor($organization);
        $documents = $validator->acknowledgmentDocumentsFor($organization);

        return view('candidate.intake', [
            'reportRequest' => $reportRequest,
            'organization' => $organization,
            'questions' => $questions,
            'documents' => $documents,
            'token' => $token,
            'inviteExpiresAt' => $reportRequest->inviteExpiresAt(),
        ]);
    }

    public function store(
        Request $request,
        string $token,
        CandidateInviteService $inviteService,
        CandidateIntakeValidator $validator,
        TenantContext $tenantContext,
    ): RedirectResponse {
        $reportRequest = $this->findInvitedRequest($token, $tenantContext);
        $inviteService->assertInviteActive($reportRequest);

        $organization = $reportRequest->organization;

        $questions = $validator->activeQuestionsFor($organization);
        $documents = $validator->acknowledgmentDocumentsFor($organization);

        $validated = $validator->validate($questions, $documents, $request->all());

        $inviteService->complete(
            $reportRequest,
            $validated['answers'],
            $validated['acknowledged_document_ids'],
            $request->ip() ?? '',
            $request->userAgent(),
        );

        $request->session()->put('candidate_completed_request_id', $reportRequest->id);

        return redirect()
            ->route('candidate.intake.thanks')
            ->with('status', 'Your information was submitted successfully.');
    }

    public function thanks(Request $request, TenantContext $tenantContext): View
    {
        $completedId = $request->session()->pull('candidate_completed_request_id');

        $reportRequest = $completedId
            ? ReportRequest::query()
                ->withoutGlobalScopes()
                ->with('organization')
                ->where('tenant_id', $tenantContext->id())
                ->where('id', $completedId)
                ->whereNotNull('candidate_completed_at')
                ->first()
            : null;

        abort_unless($reportRequest !== null, 404);

        return view('candidate.thanks', [
            'reportRequest' => $reportRequest,
            'organization' => $reportRequest->organization,
        ]);
    }

    public function downloadDocument(
        string $token,
        ComplianceDocument $complianceDocument,
        TenantContext $tenantContext,
        CandidateInviteService $inviteService,
    ): StreamedResponse {
        $reportRequest = $this->findInvitedRequest($token, $tenantContext);
        $inviteService->assertInviteActive($reportRequest);

        abort_unless($complianceDocument->organization_id === $reportRequest->organization_id, 404);
        abort_unless($complianceDocument->is_active, 404);
        abort_unless(Storage::disk($complianceDocument->disk)->exists($complianceDocument->path), 404);

        return Storage::disk($complianceDocument->disk)->download(
            $complianceDocument->path,
            $complianceDocument->original_filename,
        );
    }

    private function findInvitedRequest(string $token, TenantContext $tenantContext): ReportRequest
    {
        $reportRequest = ReportRequest::query()
            ->withoutGlobalScopes()
            ->with(['organization', 'screeningPackage'])
            ->where('tenant_id', $tenantContext->id())
            ->where('invite_token', $token)
            ->where('status', ReportRequestStatus::AwaitingCandidate)
            ->first();

        abort_unless($reportRequest !== null, 404, 'This intake link is invalid or has already been used.');

        return $reportRequest;
    }
}
