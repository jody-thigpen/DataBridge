<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Permission;
use App\Enums\ReportOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\ReportOrder;
use App\Models\SavedReportOrderFilter;
use App\Models\User;
use App\Services\SearchReviewPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportOrderController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->canView($request->user()), 403);

        $filters = $this->resolveFilters($request);

        $reportOrders = ReportOrder::query()
            ->with(['organization', 'screeningPackage', 'orderedBy', 'assignedTo'])
            ->filter($filters)
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('platform.report-orders.index', [
            'reportOrders' => $reportOrders,
            'filters' => $filters,
            'organizations' => Organization::query()->orderBy('name')->get(),
            'statuses' => ReportOrderStatus::cases(),
            'assignees' => $this->platformAssignees(),
            'savedFilters' => $request->user()
                ->savedReportOrderFilters()
                ->orderBy('name')
                ->get(),
            'canManage' => $this->canManage($request->user()),
        ]);
    }

    public function storeFilter(Request $request): RedirectResponse
    {
        abort_unless($this->canView($request->user()), 403);

        $user = $request->user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('saved_report_order_filters', 'name')->where('user_id', $user->id),
            ],
        ]);

        $filters = SavedReportOrderFilter::normalizeFilters(
            $request->only(SavedReportOrderFilter::FILTER_KEYS),
        );

        if ($filters === []) {
            return redirect()
                ->route('platform.report-orders.index')
                ->withErrors(['filter_name' => 'Apply at least one filter before saving a filter set.']);
        }

        $user->savedReportOrderFilters()->create([
            'name' => $validated['name'],
            'filters' => $filters,
        ]);

        return redirect()
            ->route('platform.report-orders.index', $filters)
            ->with('status', "Filter \"{$validated['name']}\" saved.");
    }

    public function destroyFilter(Request $request, SavedReportOrderFilter $savedReportOrderFilter): RedirectResponse
    {
        abort_unless($this->canView($request->user()), 403);
        abort_unless($savedReportOrderFilter->user_id === $request->user()->id, 403);

        $name = $savedReportOrderFilter->name;
        $savedReportOrderFilter->delete();

        return redirect()
            ->route('platform.report-orders.index', $this->resolveFilters($request))
            ->with('status', "Filter \"{$name}\" deleted.");
    }

    public function show(Request $request, ReportOrder $reportOrder, SearchReviewPolicy $searchReviewPolicy): View
    {
        abort_unless($this->canView($request->user()), 403);

        $reportOrder->load(['organization', 'screeningPackage.searchTypes', 'orderedBy', 'assignedTo', 'reviewedBy']);

        return view('platform.report-orders.show', [
            'reportOrder' => $reportOrder,
            'reviewBreakdown' => $searchReviewPolicy->reviewBreakdown(
                $reportOrder->screeningPackage,
                $reportOrder->organization,
            ),
            'assignees' => $this->platformAssignees(),
            'canManage' => $this->canManage($request->user()),
        ]);
    }

    public function assign(Request $request, ReportOrder $reportOrder): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        abort_unless($reportOrder->requires_review, 403);
        abort_if($reportOrder->status === ReportOrderStatus::AwaitingCandidate, 403);
        abort_if(in_array($reportOrder->status, [ReportOrderStatus::Submitted, ReportOrderStatus::Rejected, ReportOrderStatus::Cancelled], true), 403);

        $assigneeIds = $this->platformAssignees()->pluck('id');

        $validated = $request->validate([
            'assigned_to_user_id' => ['required', Rule::in($assigneeIds)],
        ]);

        $reportOrder->update([
            'assigned_to_user_id' => $validated['assigned_to_user_id'],
            'assigned_at' => now(),
            'status' => ReportOrderStatus::Assigned,
        ]);

        return back()->with('status', 'Report order assigned for review.');
    }

    public function approve(Request $request, ReportOrder $reportOrder): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        abort_unless($reportOrder->requires_review, 403);
        abort_if($reportOrder->status === ReportOrderStatus::AwaitingCandidate, 403);
        abort_if(in_array($reportOrder->status, [ReportOrderStatus::Submitted, ReportOrderStatus::Rejected, ReportOrderStatus::Cancelled], true), 403);

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $reportOrder->update([
            'status' => ReportOrderStatus::Submitted,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'submitted_at' => now(),
            'review_notes' => $validated['review_notes'] ?? null,
        ]);

        return back()->with('status', 'Report order approved and submitted for execution.');
    }

    public function reject(Request $request, ReportOrder $reportOrder): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        abort_unless($reportOrder->requires_review, 403);
        abort_if($reportOrder->status === ReportOrderStatus::AwaitingCandidate, 403);
        abort_if(in_array($reportOrder->status, [ReportOrderStatus::Submitted, ReportOrderStatus::Rejected, ReportOrderStatus::Cancelled], true), 403);

        $validated = $request->validate([
            'review_notes' => ['required', 'string', 'max:2000'],
        ]);

        $reportOrder->update([
            'status' => ReportOrderStatus::Rejected,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'],
        ]);

        return back()->with('status', 'Report order rejected.');
    }

    private function canView(?User $user): bool
    {
        return $user !== null && $user->hasPermission(Permission::PlatformReportOrdersView);
    }

    private function canManage(?User $user): bool
    {
        return $user !== null && $user->hasPermission(Permission::PlatformReportOrdersManage);
    }

    private function platformAssignees()
    {
        return User::query()
            ->whereHas('roleAssignments', fn ($query) => $query->whereNull('organization_id'))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    private function resolveFilters(Request $request): array
    {
        return SavedReportOrderFilter::normalizeFilters(
            $request->only(SavedReportOrderFilter::FILTER_KEYS),
        );
    }
}
