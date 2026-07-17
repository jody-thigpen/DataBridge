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
                        <th>Candidate email</th>
                        <th>Package</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Assigned to</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reportRequests as $reportRequest)
                        <tr>
                            <td class="text-enterprise-600">{{ $reportRequest->created_at->format('M j, Y g:i A') }}</td>
                            <td class="font-medium text-enterprise-900">{{ $reportRequest->subject_name }}</td>
                            <td class="text-enterprise-600">{{ $reportRequest->candidate_email ?? '—' }}</td>
                            <td class="text-enterprise-600">{{ $reportRequest->screeningPackage->name }}</td>
                            <td class="text-enterprise-600">{{ $reportRequest->formattedPrice() }}</td>
                            <td>
                                <span @class(['badge', $reportRequest->status->badgeClass()])>
                                    {{ $reportRequest->status->label() }}
                                </span>
                            </td>
                            <td class="text-enterprise-600">{{ $reportRequest->assignedTo?->name ?? '—' }}</td>
                            <td class="text-right space-y-1">
                                @if ($canCreate && $reportRequest->isAwaitingCandidate())
                                    @if ($reportRequest->isInviteExpired())
                                        <div class="text-xs text-red-600">Invite expired</div>
                                    @elseif ($reportRequest->inviteExpiresAt())
                                        <div class="text-xs text-enterprise-500">
                                            Expires {{ $reportRequest->inviteExpiresAt()->format('M j, Y') }}
                                        </div>
                                    @endif
                                    <form method="POST" action="{{ route('reports.requests.resend-invite', $reportRequest) }}">
                                        @csrf
                                        <button type="submit" class="link-action">
                                            {{ $reportRequest->isInviteExpired() ? 'Resend expired invite' : 'Resend invite' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-enterprise-500">No report requests submitted yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $reportRequests->links() }}</div>
</x-app-layout>
