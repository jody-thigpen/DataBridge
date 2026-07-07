<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Report requests" subtitle="All client screening requests awaiting review or execution.">
        </x-page-header>
    </x-slot>

    <div class="panel mb-5">
        <form method="GET" action="{{ route('platform.report-requests.index') }}" class="panel-body grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <x-input-label for="q" value="Search" />
                <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$filters['q'] ?? ''" placeholder="Subject, client, package, requester..." />
            </div>
            <div>
                <x-input-label for="organization_id" value="Client" />
                <select id="organization_id" name="organization_id" class="mt-1 block w-full">
                    <option value="">All clients</option>
                    @foreach ($organizations as $organization)
                        <option value="{{ $organization->id }}" @selected(($filters['organization_id'] ?? '') == $organization->id)>{{ $organization->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="mt-1 block w-full">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="assigned_to_user_id" value="Assigned reviewer" />
                <select id="assigned_to_user_id" name="assigned_to_user_id" class="mt-1 block w-full">
                    <option value="">Anyone</option>
                    <option value="unassigned" @selected(($filters['assigned_to_user_id'] ?? '') === 'unassigned')>Unassigned</option>
                    @foreach ($assignees as $assignee)
                        <option value="{{ $assignee->id }}" @selected(($filters['assigned_to_user_id'] ?? '') == $assignee->id)>{{ $assignee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="requires_review" value="Review required" />
                <select id="requires_review" name="requires_review" class="mt-1 block w-full">
                    <option value="">All</option>
                    <option value="1" @selected(($filters['requires_review'] ?? '') === '1')>Yes</option>
                    <option value="0" @selected(($filters['requires_review'] ?? '') === '0')>No (auto-submit)</option>
                </select>
            </div>
            <div>
                <x-input-label for="date_from" value="From date" />
                <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="$filters['date_from'] ?? ''" />
            </div>
            <div>
                <x-input-label for="date_to" value="To date" />
                <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="$filters['date_to'] ?? ''" />
            </div>
            <div class="flex items-end gap-2">
                <x-primary-button>Apply filters</x-primary-button>
                <a href="{{ route('platform.report-requests.index') }}" class="btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Requested</th>
                        <th>Client</th>
                        <th>Subject</th>
                        <th>Package</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Reviewer</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reportRequests as $reportRequest)
                        <tr>
                            <td class="text-enterprise-600 whitespace-nowrap">{{ $reportRequest->created_at->format('M j, Y g:i A') }}</td>
                            <td class="text-enterprise-600">{{ $reportRequest->organization->name }}</td>
                            <td class="font-medium text-enterprise-900">{{ $reportRequest->subject_name }}</td>
                            <td class="text-enterprise-600">{{ $reportRequest->screeningPackage->name }}</td>
                            <td class="text-enterprise-600">{{ $reportRequest->formattedPrice() }}</td>
                            <td>
                                <span @class(['badge', $reportRequest->status->badgeClass()])>
                                    {{ $reportRequest->status->label() }}
                                </span>
                            </td>
                            <td class="text-enterprise-600">{{ $reportRequest->assignedTo?->name ?? '—' }}</td>
                            <td class="text-right">
                                <a href="{{ route('platform.report-requests.show', $reportRequest) }}" class="link-action">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-enterprise-500">No report requests match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $reportRequests->links() }}</div>
</x-app-layout>
