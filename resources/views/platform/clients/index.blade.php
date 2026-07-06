<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Client organizations"
            subtitle="All registered client accounts and assigned users."
        >
            @if ($canManageClients)
                <x-slot name="actions">
                    <a href="{{ route('platform.clients.create') }}" class="btn-primary">New client</a>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>

    <div class="panel">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Organization</th>
                        <th>Parent company</th>
                        <th>Users</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($organizations as $organization)
                        <tr>
                            <td class="font-medium text-enterprise-900">{{ $organization->name }}</td>
                            <td class="text-enterprise-600">{{ $organization->parent?->name ?? '—' }}</td>
                            <td class="text-enterprise-600">{{ $organization->users_count }}</td>
                            <td>
                                <span @class(['badge', 'badge-success' => $organization->is_active, 'badge-muted' => ! $organization->is_active])>
                                    {{ $organization->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('platform.clients.show', $organization) }}" class="link-action">View details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-enterprise-500">No client organizations on record.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $organizations->links() }}
    </div>
</x-app-layout>
