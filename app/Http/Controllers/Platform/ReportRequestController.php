<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Permission;
use App\Enums\ReportRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\ReportRequest;
use App\Models\User;
use App\Services\SearchReviewPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportRequestController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->canView($request->user()), 403);

        $filters = $request->only([
            'organization_id',
            'status',
            'assigned_to_user_id',
            'requires_review',
            'date_from',
            'date_to',
            'q',
        ]);

        $reportRequests = ReportRequest::query()
            ->with(['organization', 'screeningPackage', 'requestedBy', 'assignedTo'])
            ->filter($filters)
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('platform.report-requests.index', [
            'reportRequests' => $reportRequests,
            'filters' => $filters,
            'organizations' => Organization::query()->orderBy('name')->get(),
            'statuses' => ReportRequestStatus::cases(),
            'assignees' => $this->platformAssignees(),
            'canManage' => $this->canManage($request->user()),
        ]);
    }

    public function show(Request $request, ReportRequest $reportRequest, SearchReviewPolicy $searchReviewPolicy): View
    {
        abort_unless($this->canView($request->user()), 403);

        $reportRequest->load(['organization', 'screeningPackage.searchTypes', 'requestedBy', 'assignedTo', 'reviewedBy']);

        return view('platform.report-requests.show', [
            'reportRequest' => $reportRequest,
            'reviewBreakdown' => $searchReviewPolicy->reviewBreakdown(
                $reportRequest->screeningPackage,
                $reportRequest->organization,
            ),
            'assignees' => $this->platformAssignees(),
            'canManage' => $this->canManage($request->user()),
        ]);
    }

    public function assign(Request $request, ReportRequest $reportRequest): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        abort_unless($reportRequest->requires_review, 403);
        abort_if(in_array($reportRequest->status, [ReportRequestStatus::Submitted, ReportRequestStatus::Rejected, ReportRequestStatus::Cancelled], true), 403);

        $assigneeIds = $this->platformAssignees()->pluck('id');

        $validated = $request->validate([
            'assigned_to_user_id' => ['required', Rule::in($assigneeIds)],
        ]);

        $reportRequest->update([
            'assigned_to_user_id' => $validated['assigned_to_user_id'],
            'assigned_at' => now(),
            'status' => ReportRequestStatus::Assigned,
        ]);

        return back()->with('status', 'Report request assigned for review.');
    }

    public function approve(Request $request, ReportRequest $reportRequest): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        abort_unless($reportRequest->requires_review, 403);
        abort_if(in_array($reportRequest->status, [ReportRequestStatus::Submitted, ReportRequestStatus::Rejected, ReportRequestStatus::Cancelled], true), 403);

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $reportRequest->update([
            'status' => ReportRequestStatus::Submitted,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'submitted_at' => now(),
            'review_notes' => $validated['review_notes'] ?? null,
        ]);

        return back()->with('status', 'Report request approved and submitted for execution.');
    }

    public function reject(Request $request, ReportRequest $reportRequest): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        abort_unless($reportRequest->requires_review, 403);
        abort_if(in_array($reportRequest->status, [ReportRequestStatus::Submitted, ReportRequestStatus::Rejected, ReportRequestStatus::Cancelled], true), 403);

        $validated = $request->validate([
            'review_notes' => ['required', 'string', 'max:2000'],
        ]);

        $reportRequest->update([
            'status' => ReportRequestStatus::Rejected,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'],
        ]);

        return back()->with('status', 'Report request rejected.');
    }

    private function canView(?User $user): bool
    {
        return $user !== null && $user->hasPermission(Permission::PlatformReportRequestsView);
    }

    private function canManage(?User $user): bool
    {
        return $user !== null && $user->hasPermission(Permission::PlatformReportRequestsManage);
    }

    private function platformAssignees()
    {
        return User::query()
            ->whereHas('roleAssignments', fn ($query) => $query->whereNull('organization_id'))
            ->orderBy('name')
            ->get();
    }
}
