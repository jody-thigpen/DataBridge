<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$organization->name" subtitle="Client organization record and support actions.">
            <x-slot name="actions">
                <a href="{{ route('platform.clients.index') }}" class="btn-secondary">Back to clients</a>
                <form method="POST" action="{{ route('platform.clients.enter', $organization) }}">
                    @csrf
                    <button type="submit" class="btn-primary">Enter organization</button>
                </form>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="panel lg:col-span-1">
            <div class="panel-header">
                <h2 class="panel-title">Organization details</h2>
            </div>
            <div class="panel-body space-y-4">
                <div>
                    <div class="meta-label">Identifier</div>
                    <div class="meta-value">{{ $organization->slug }}</div>
                </div>
                <div>
                    <div class="meta-label">Parent company</div>
                    <div class="meta-value">{{ $organization->parent?->name ?? 'None' }}</div>
                </div>
                @if ($organization->children->isNotEmpty())
                    <div>
                        <div class="meta-label">Subsidiaries</div>
                        <ul class="mt-1 list-disc space-y-1 pl-5 text-sm text-enterprise-700">
                            @foreach ($organization->children as $child)
                                <li>{{ $child->name }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        <div class="panel lg:col-span-2">
            <div class="panel-header">
                <h2 class="panel-title">Assigned users</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="text-right">Support</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($organization->roleAssignments as $assignment)
                            <tr>
                                <td class="font-medium text-enterprise-900">{{ $assignment->user->name }}</td>
                                <td class="text-enterprise-600">{{ $assignment->user->email }}</td>
                                <td class="text-enterprise-600">{{ $assignment->role->name }}</td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('platform.impersonation.store', $assignment->user) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="link-action">Start support session</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center text-enterprise-500">No users assigned to this organization.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
