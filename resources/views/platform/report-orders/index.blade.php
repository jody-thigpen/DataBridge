<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Report orders" subtitle="All client screening orders awaiting review or execution.">
        </x-page-header>
    </x-slot>

    <div class="panel mb-5">
        <form method="GET" action="{{ route('platform.report-orders.index') }}" class="panel-body grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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
                <a href="{{ route('platform.report-orders.index') }}" class="btn-secondary">Reset</a>
            </div>
        </form>

        <div class="border-t border-enterprise-200 px-4 py-4">
            <form method="POST" action="{{ route('platform.report-orders.filters.store') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                @foreach (\App\Models\SavedReportOrderFilter::FILTER_KEYS as $filterKey)
                    @if (! empty($filters[$filterKey]))
                        <input type="hidden" name="{{ $filterKey }}" value="{{ $filters[$filterKey] }}">
                    @endif
                @endforeach
                <div class="min-w-[16rem] flex-1">
                    <x-input-label for="filter_name" value="Save current filters as" />
                    <x-text-input id="filter_name" name="name" class="mt-1 block w-full" :value="old('name')" placeholder="e.g. Pending client reviews" required />
                    <x-input-error :messages="$errors->get('filter_name')" class="mt-2" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <x-primary-button>Save filter set</x-primary-button>
            </form>
        </div>

        @if ($savedFilters->isNotEmpty())
            <div class="border-t border-enterprise-200 px-4 py-4">
                <h3 class="text-sm font-semibold text-enterprise-900">Saved filter sets</h3>
                <ul class="mt-3 space-y-2">
                    @foreach ($savedFilters as $savedFilter)
                        <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-enterprise-200 px-3 py-2">
                            <div>
                                <a href="{{ route('platform.report-orders.index', $savedFilter->filters) }}" class="font-medium text-brand-700 hover:text-brand-800">
                                    {{ $savedFilter->name }}
                                </a>
                                <p class="text-xs text-enterprise-500">
                                    {{ collect($savedFilter->filters)->count() }} filter{{ collect($savedFilter->filters)->count() === 1 ? '' : 's' }}
                                </p>
                            </div>
                            <form
                                method="POST"
                                action="{{ route('platform.report-orders.filters.destroy', $savedFilter) }}"
                                class="inline"
                                onsubmit="return confirm('Delete saved filter \"{{ $savedFilter->name }}\"?');"
                            >
                                @csrf
                                @method('DELETE')
                                @foreach ($filters as $filterKey => $filterValue)
                                    <input type="hidden" name="{{ $filterKey }}" value="{{ $filterValue }}">
                                @endforeach
                                <button type="submit" class="link-action text-red-700">Delete</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="panel">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ordered</th>
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
                    @forelse ($reportOrders as $reportOrder)
                        <tr>
                            <td class="text-enterprise-600 whitespace-nowrap">{{ $reportOrder->created_at->format('M j, Y g:i A') }}</td>
                            <td class="text-enterprise-600">{{ $reportOrder->organization->name }}</td>
                            <td class="font-medium text-enterprise-900">{{ $reportOrder->subject_name }}</td>
                            <td class="text-enterprise-600">{{ $reportOrder->screeningPackage->name }}</td>
                            <td class="text-enterprise-600">{{ $reportOrder->formattedPrice() }}</td>
                            <td>
                                <span @class(['badge', $reportOrder->status->badgeClass()])>
                                    {{ $reportOrder->status->label() }}
                                </span>
                            </td>
                            <td class="text-enterprise-600">{{ $reportOrder->assignedTo?->name ?? '—' }}</td>
                            <td class="text-right">
                                <a href="{{ route('platform.report-orders.show', $reportOrder) }}" class="link-action">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-enterprise-500">No report orders match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $reportOrders->links() }}</div>
</x-app-layout>
