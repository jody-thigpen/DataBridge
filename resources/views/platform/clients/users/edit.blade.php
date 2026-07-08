<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="'Edit ' . $user->name" :subtitle="'Update team member for ' . $organization->name">
            <x-slot name="actions">
                <a href="{{ route('platform.clients.show', $organization) }}" class="btn-secondary">Cancel</a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <form method="POST" action="{{ route('platform.clients.users.update', [$organization, $user]) }}" class="space-y-5">
        @csrf
        @method('PATCH')

        @include('partials.managed-user-edit-fields', [
            'user' => $user,
            'roles' => $organizationRoles,
            'currentRoleId' => $currentRoleId,
            'roleLabel' => 'Organization role',
        ])

        <x-primary-button>Save changes</x-primary-button>
    </form>
</x-app-layout>
