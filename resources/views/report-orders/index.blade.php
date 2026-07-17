<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Report orders"
            :subtitle="'Submitted screening orders for ' . $organization->name"
        >
            @if ($canCreate)
                <x-slot name="actions">
                    <a href="{{ route('report-orders.create') }}" class="btn-primary">New report order</a>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>

    <div class="panel">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ordered</th>
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
                    @forelse ($reportOrders as $reportOrder)
                        <tr>
                            <td class="text-enterprise-600">{{ $reportOrder->created_at->format('M j, Y g:i A') }}</td>
                            <td class="font-medium text-enterprise-900">{{ $reportOrder->subject_name }}</td>
                            <td class="text-enterprise-600">{{ $reportOrder->candidate_email ?? '—' }}</td>
                            <td class="text-enterprise-600">{{ $reportOrder->screeningPackage->name }}</td>
                            <td class="text-enterprise-600">{{ $reportOrder->formattedPrice() }}</td>
                            <td>
                                <span @class(['badge', $reportOrder->status->badgeClass()])>
                                    {{ $reportOrder->status->label() }}
                                </span>
                            </td>
                            <td class="text-enterprise-600">{{ $reportOrder->assignedTo?->name ?? '—' }}</td>
                            <td class="text-right space-y-1">
                                @if ($canCreate && $reportOrder->isAwaitingCandidate())
                                    @if ($reportOrder->isInviteExpired())
                                        <div class="text-xs text-red-600">Invite expired</div>
                                    @elseif ($reportOrder->inviteExpiresAt())
                                        <div class="text-xs text-enterprise-500">
                                            Expires {{ $reportOrder->inviteExpiresAt()->format('M j, Y') }}
                                        </div>
                                    @endif
                                    <form method="POST" action="{{ route('report-orders.resend-invite', $reportOrder) }}">
                                        @csrf
                                        <button type="submit" class="link-action">
                                            {{ $reportOrder->isInviteExpired() ? 'Resend expired invite' : 'Resend invite' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-enterprise-500">No report orders submitted yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $reportOrders->links() }}</div>
</x-app-layout>
