<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Report requests"
            :subtitle="'Submitted screening requests for ' . $organization->name"
        >
            @if ($canCreate)
                <x-slot name="actions">
                    <a href="{{ route('reports.requests.create') }}" class="btn-primary">New report request</a>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>

    <div class="panel">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Requested</th>
                        <th>Subject</th>
                        <th>Package</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Assigned to</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reportRequests as $reportRequest)
                        <tr>
                            <td class="text-enterprise-600">{{ $reportRequest->created_at->format('M j, Y g:i A') }}</td>
                            <td class="font-medium text-enterprise-900">{{ $reportRequest->subject_name }}</td>
                            <td class="text-enterprise-600">{{ $reportRequest->screeningPackage->name }}</td>
                            <td class="text-enterprise-600">{{ $reportRequest->formattedPrice() }}</td>
                            <td>
                                <span @class(['badge', $reportRequest->status->badgeClass()])>
                                    {{ $reportRequest->status->label() }}
                                </span>
                            </td>
                            <td class="text-enterprise-600">{{ $reportRequest->assignedTo?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-enterprise-500">No report requests submitted yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $reportRequests->links() }}</div>
</x-app-layout>
