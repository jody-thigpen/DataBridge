<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$reportOrder->subject_name" subtitle="Report order review and assignment.">
            <x-slot name="actions">
                <a href="{{ route('platform.report-orders.index') }}" class="btn-secondary">Back to queue</a>
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
                        <a href="{{ route('platform.clients.show', $reportOrder->organization) }}" class="link-action">{{ $reportOrder->organization->name }}</a>
                    </div>
                </div>
                <div>
                    <div class="meta-label">Package</div>
                    <div class="meta-value">{{ $reportOrder->screeningPackage->name }}</div>
                </div>
                <div>
                    <div class="meta-label">Price</div>
                    <div class="meta-value">{{ $reportOrder->formattedPrice() }}</div>
                </div>
                <div>
                    <div class="meta-label">Requested by</div>
                    <div class="meta-value">{{ $reportOrder->orderedBy->name }} ({{ $reportOrder->orderedBy->email }})</div>
                </div>
                <div>
                    <div class="meta-label">Requested at</div>
                    <div class="meta-value">{{ $reportOrder->created_at->format('M j, Y g:i A') }}</div>
                </div>
                <div>
                    <div class="meta-label">Status</div>
                    <div class="meta-value">
                        <span @class(['badge', $reportOrder->status->badgeClass()])>{{ $reportOrder->status->label() }}</span>
                    </div>
                </div>
                <div>
                    <div class="meta-label">Candidate email</div>
                    <div class="meta-value">{{ $reportOrder->candidate_email ?? '—' }}</div>
                </div>
                @if ($reportOrder->candidate_phone)
                    <div>
                        <div class="meta-label">Candidate phone</div>
                        <div class="meta-value">{{ $reportOrder->candidate_phone }}</div>
                    </div>
                @endif
                <div>
                    <div class="meta-label">Invite sent</div>
                    <div class="meta-value">{{ $reportOrder->invite_sent_at?->format('M j, Y g:i A') ?? '—' }}</div>
                </div>
                @if ($reportOrder->isAwaitingCandidate() && $reportOrder->inviteExpiresAt())
                    <div>
                        <div class="meta-label">Invite expires</div>
                        <div class="meta-value">
                            {{ $reportOrder->inviteExpiresAt()->format('M j, Y g:i A') }}
                            @if ($reportOrder->isInviteExpired())
                                <span class="badge badge-muted ml-2">Expired</span>
                            @endif
                        </div>
                    </div>
                @endif
                <div>
                    <div class="meta-label">Candidate completed</div>
                    <div class="meta-value">{{ $reportOrder->candidate_completed_at?->format('M j, Y g:i A') ?? 'Not yet' }}</div>
                </div>
                <div>
                    <div class="meta-label">Authorization accepted</div>
                    <div class="meta-value">
                        @if ($reportOrder->authorization_accepted_at)
                            {{ $reportOrder->authorization_accepted_at->format('M j, Y g:i A') }}
                            @if ($reportOrder->authorization_ip)
                                <div class="text-xs text-enterprise-500">IP {{ $reportOrder->authorization_ip }}</div>
                            @endif
                        @else
                            Not yet
                        @endif
                    </div>
                </div>
                <div>
                    <div class="meta-label">Review required</div>
                    <div class="meta-value">{{ $reportOrder->requires_review ? 'Yes' : 'No (auto-submitted)' }}</div>
                </div>
                @if ($reportOrder->notes)
                    <div>
                        <div class="meta-label">Client notes</div>
                        <div class="meta-value text-sm text-enterprise-700">{{ $reportOrder->notes }}</div>
                    </div>
                @endif
                @if ($reportOrder->review_notes)
                    <div>
                        <div class="meta-label">Review notes</div>
                        <div class="meta-value text-sm text-enterprise-700">{{ $reportOrder->review_notes }}</div>
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

    @if ($reportOrder->candidateHasCompleted())
        <div class="panel mt-5">
            <div class="panel-header"><h2 class="panel-title">Candidate intake responses</h2></div>
            <div class="panel-body space-y-4">
                @forelse (($reportOrder->candidate_answers ?? []) as $key => $answer)
                    <div>
                        <div class="meta-label">{{ str_replace('_', ' ', ucfirst($key)) }}</div>
                        <div class="meta-value text-sm text-enterprise-700">
                            @if (is_array($answer))
                                <pre class="whitespace-pre-wrap font-sans text-sm">{{ json_encode($answer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                {{ $answer ?: '—' }}
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-enterprise-500">No answers recorded.</p>
                @endforelse
            </div>
        </div>
    @elseif ($reportOrder->isAwaitingCandidate())
        <div class="panel mt-5">
            <div class="panel-body text-sm text-enterprise-600">
                Waiting for the candidate to complete the intake form and authorize the screening. Review actions are unavailable until intake is complete.
            </div>
        </div>
    @endif

    @if ($canManage && $reportOrder->requires_review && ! $reportOrder->isAwaitingCandidate() && ! in_array($reportOrder->status, [\App\Enums\ReportOrderStatus::Submitted, \App\Enums\ReportOrderStatus::Rejected, \App\Enums\ReportOrderStatus::Cancelled], true))
        <div class="mt-5 grid gap-5 lg:grid-cols-3">
            <div class="panel">
                <div class="panel-header"><h2 class="panel-title">Assign reviewer</h2></div>
                <form method="POST" action="{{ route('platform.report-orders.assign', $reportOrder) }}" class="panel-body space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <x-input-label for="assigned_to_user_id" value="Assigned reviewer" />
                        <select id="assigned_to_user_id" name="assigned_to_user_id" class="mt-1 block w-full" required>
                            <option value="">Select reviewer</option>
                            @foreach ($assignees as $assignee)
                                <option value="{{ $assignee->id }}" @selected(old('assigned_to_user_id', $reportOrder->assigned_to_user_id) == $assignee->id)>{{ $assignee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-primary-button>Assign for review</x-primary-button>
                </form>
            </div>

            <div class="panel">
                <div class="panel-header"><h2 class="panel-title">Approve & submit</h2></div>
                <form method="POST" action="{{ route('platform.report-orders.approve', $reportOrder) }}" class="panel-body space-y-4">
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
                <div class="panel-header"><h2 class="panel-title">Reject order</h2></div>
                <form method="POST" action="{{ route('platform.report-orders.reject', $reportOrder) }}" class="panel-body space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <x-input-label for="reject_review_notes" value="Rejection reason" />
                        <textarea id="reject_review_notes" name="review_notes" rows="3" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>{{ old('review_notes') }}</textarea>
                    </div>
                    <x-primary-button>Reject order</x-primary-button>
                </form>
            </div>
        </div>
    @endif
</x-app-layout>
