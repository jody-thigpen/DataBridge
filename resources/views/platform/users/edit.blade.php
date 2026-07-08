<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="'Edit ' . $user->name" subtitle="Update account details and platform role.">
            <x-slot name="actions">
                <a href="{{ route('platform.users.index') }}" class="btn-secondary">Cancel</a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <form method="POST" action="{{ route('platform.users.update', $user) }}" class="space-y-5">
        @csrf
        @method('PATCH')

        @include('partials.managed-user-edit-fields', [
            'user' => $user,
            'roles' => $platformRoles,
            'currentRoleId' => $currentRoleId,
            'roleLabel' => 'Platform role',
        ])

        <x-primary-button>Save changes</x-primary-button>
    </form>
</x-app-layout>
