<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Organization users"
            :subtitle="'Team access management for ' . $organization->name"
        />
    </x-slot>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="panel lg:col-span-2">
            <div class="panel-header">
                <h2 class="panel-title">Team members</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            @if ($canManageUsers)
                                <th class="text-right">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="font-medium text-enterprise-900">{{ $user->name }}</td>
                                <td class="text-enterprise-600">{{ $user->email }}</td>
                                <td class="text-enterprise-600">{{ $user->roleAssignments->first()?->role->name }}</td>
                                @if ($canManageUsers)
                                    <td class="text-right">
                                        <a href="{{ route('organization.users.edit', $user) }}" class="link-action">Edit</a>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canManageUsers ? 4 : 3 }}" class="py-10 text-center text-enterprise-500">No team members on record.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-enterprise-200 px-4 py-3">
                {{ $users->links() }}
            </div>
        </div>

        @if ($canAddUsers)
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title">Add team member</h2>
                </div>
                <form method="POST" action="{{ route('organization.users.store') }}" class="panel-body space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="name" value="Full name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Work email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="password" value="Temporary password" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="role_id" value="Organization role" />
                        <select id="role_id" name="role_id" class="mt-1 block w-full">
                            @foreach ($organizationRoles as $role)
                                @if ($canManageUsers || $role->slug !== \App\Enums\OrganizationRole::ClientAdmin->value)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                    </div>
                    <x-primary-button>Add member</x-primary-button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
