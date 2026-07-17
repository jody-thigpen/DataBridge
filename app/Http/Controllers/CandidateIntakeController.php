<?php

namespace App\Http\Controllers;

use App\Enums\ReportOrderStatus;
use App\Models\ComplianceDocument;
use App\Models\ReportOrder;
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
        $reportOrder = $this->findInvitedOrder($token, $tenantContext);

        if ($reportOrder->isInviteExpired()) {
            return view('candidate.expired', [
                'organization' => $reportOrder->organization,
                'ttlDays' => $inviteService->inviteTtlDays(),
            ]);
        }

        $inviteService->markOpened($reportOrder);

        $organization = $reportOrder->organization;
        $questions = $validator->activeQuestionsFor($organization);
        $documents = $validator->acknowledgmentDocumentsFor($organization);

        return view('candidate.intake', [
            'reportOrder' => $reportOrder,
            'organization' => $organization,
            'questions' => $questions,
            'documents' => $documents,
            'token' => $token,
            'inviteExpiresAt' => $reportOrder->inviteExpiresAt(),
        ]);
    }

    public function store(
        Request $request,
        string $token,
        CandidateInviteService $inviteService,
        CandidateIntakeValidator $validator,
        TenantContext $tenantContext,
    ): RedirectResponse {
        $reportOrder = $this->findInvitedOrder($token, $tenantContext);
        $inviteService->assertInviteActive($reportOrder);

        $organization = $reportOrder->organization;

        $questions = $validator->activeQuestionsFor($organization);
        $documents = $validator->acknowledgmentDocumentsFor($organization);

        $validated = $validator->validate($questions, $documents, $request->all());

        $inviteService->complete(
            $reportOrder,
            $validated['answers'],
            $validated['acknowledged_document_ids'],
            $request->ip() ?? '',
            $request->userAgent(),
        );

        $request->session()->put('candidate_completed_order_id', $reportOrder->id);

        return redirect()
            ->route('candidate.intake.thanks')
            ->with('status', 'Your information was submitted successfully.');
    }

    public function thanks(Request $request, TenantContext $tenantContext): View
    {
        $completedId = $request->session()->pull('candidate_completed_order_id');

        $reportOrder = $completedId
            ? ReportOrder::query()
                ->withoutGlobalScopes()
                ->with('organization')
                ->where('tenant_id', $tenantContext->id())
                ->where('id', $completedId)
                ->whereNotNull('candidate_completed_at')
                ->first()
            : null;

        abort_unless($reportOrder !== null, 404);

        return view('candidate.thanks', [
            'reportOrder' => $reportOrder,
            'organization' => $reportOrder->organization,
        ]);
    }

    public function downloadDocument(
        string $token,
        ComplianceDocument $complianceDocument,
        TenantContext $tenantContext,
        CandidateInviteService $inviteService,
    ): StreamedResponse {
        $reportOrder = $this->findInvitedOrder($token, $tenantContext);
        $inviteService->assertInviteActive($reportOrder);

        abort_unless($complianceDocument->organization_id === $reportOrder->organization_id, 404);
        abort_unless($complianceDocument->is_active, 404);
        abort_unless(Storage::disk($complianceDocument->disk)->exists($complianceDocument->path), 404);

        return Storage::disk($complianceDocument->disk)->download(
            $complianceDocument->path,
            $complianceDocument->original_filename,
        );
    }

    private function findInvitedOrder(string $token, TenantContext $tenantContext): ReportOrder
    {
        $reportOrder = ReportOrder::query()
            ->withoutGlobalScopes()
            ->with(['organization', 'screeningPackage'])
            ->where('tenant_id', $tenantContext->id())
            ->where('invite_token', $token)
            ->where('status', ReportOrderStatus::AwaitingCandidate)
            ->first();

        abort_unless($reportOrder !== null, 404, 'This intake link is invalid or has already been used.');

        return $reportOrder;
    }
}
