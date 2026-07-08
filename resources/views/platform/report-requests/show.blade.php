<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$reportRequest->subject_name" subtitle="Report request review and assignment.">
            <x-slot name="actions">
                <a href="{{ route('platform.report-requests.index') }}" class="btn-secondary">Back to queue</a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="panel lg:col-span-1">
            <div class="panel-header"><h2 class="panel-title">Request summary</h2></div>
            <div class="panel-body space-y-4">
                <div>
                    <div class="meta-label">Client</div>
                    <div class="meta-value">
                        <a href="{{ route('platform.clients.show', $reportRequest->organization) }}" class="link-action">{{ $reportRequest->organization->name }}</a>
                    </div>
                </div>
                <div>
                    <div class="meta-label">Package</div>
                    <div class="meta-value">{{ $reportRequest->screeningPackage->name }}</div>
                </div>
                <div>
                    <div class="meta-label">Price</div>
                    <div class="meta-value">{{ $reportRequest->formattedPrice() }}</div>
                </div>
                <div>
                    <div class="meta-label">Requested by</div>
                    <div class="meta-value">{{ $reportRequest->requestedBy->name }} ({{ $reportRequest->requestedBy->email }})</div>
                </div>
                <div>
                    <div class="meta-label">Requested at</div>
                    <div class="meta-value">{{ $reportRequest->created_at->format('M j, Y g:i A') }}</div>
                </div>
                <div>
                    <div class="meta-label">Status</div>
                    <div class="meta-value">
                        <span @class(['badge', $reportRequest->status->badgeClass()])>{{ $reportRequest->status->label() }}</span>
                    </div>
                </div>
                <div>
                    <div class="meta-label">Review required</div>
                    <div class="meta-value">{{ $reportRequest->requires_review ? 'Yes' : 'No (auto-submitted)' }}</div>
                </div>
                @if ($reportRequest->notes)
                    <div>
                        <div class="meta-label">Client notes</div>
                        <div class="meta-value text-sm text-enterprise-700">{{ $reportRequest->notes }}</div>
                    </div>
                @endif
                @if ($reportRequest->review_notes)
                    <div>
                        <div class="meta-label">Review notes</div>
                        <div class="meta-value text-sm text-enterprise-700">{{ $reportRequest->review_notes }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="panel lg:col-span-2">
            <div class="panel-header"><h2 class="panel-title">Search review policy</h2></div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Search</th>
                            <th>Requires review</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reviewBreakdown as $row)
                            <tr>
                                <td class="font-medium text-enterprise-900">{{ $row['search_type']->name }}</td>
                                <td class="text-enterprise-600">{{ $row['requires_review'] ? 'Yes' : 'No' }}</td>
                                <td class="text-enterprise-600">{{ $row['source'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($canManage && $reportRequest->requires_review && ! in_array($reportRequest->status, [\App\Enums\ReportRequestStatus::Submitted, \App\Enums\ReportRequestStatus::Rejected, \App\Enums\ReportRequestStatus::Cancelled], true))
        <div class="mt-5 grid gap-5 lg:grid-cols-3">
            <div class="panel">
                <div class="panel-header"><h2 class="panel-title">Assign reviewer</h2></div>
                <form method="POST" action="{{ route('platform.report-requests.assign', $reportRequest) }}" class="panel-body space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <x-input-label for="assigned_to_user_id" value="Assigned reviewer" />
                        <select id="assigned_to_user_id" name="assigned_to_user_id" class="mt-1 block w-full" required>
                            <option value="">Select reviewer</option>
                            @foreach ($assignees as $assignee)
                                <option value="{{ $assignee->id }}" @selected(old('assigned_to_user_id', $reportRequest->assigned_to_user_id) == $assignee->id)>{{ $assignee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-primary-button>Assign for review</x-primary-button>
                </form>
            </div>

            <div class="panel">
                <div class="panel-header"><h2 class="panel-title">Approve & submit</h2></div>
                <form method="POST" action="{{ route('platform.report-requests.approve', $reportRequest) }}" class="panel-body space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <x-input-label for="approve_review_notes" value="Review notes (optional)" />
                        <textarea id="approve_review_notes" name="review_notes" rows="3" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('review_notes') }}</textarea>
                    </div>
                    <x-primary-button>Approve and submit</x-primary-button>
                </form>
            </div>

            <div class="panel">
                <div class="panel-header"><h2 class="panel-title">Reject request</h2></div>
                <form method="POST" action="{{ route('platform.report-requests.reject', $reportRequest) }}" class="panel-body space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <x-input-label for="reject_review_notes" value="Rejection reason" />
                        <textarea id="reject_review_notes" name="review_notes" rows="3" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>{{ old('review_notes') }}</textarea>
                    </div>
                    <x-primary-button>Reject request</x-primary-button>
                </form>
            </div>
        </div>
    @endif
</x-app-layout>
