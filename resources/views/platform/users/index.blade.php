<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Platform users"
            subtitle="Saffhire staff accounts with in-house system access."
        />
    </x-slot>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="panel lg:col-span-2">
            <div class="panel-header">
                <h2 class="panel-title">Current staff accounts</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Assigned roles</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="font-medium text-enterprise-900">{{ $user->name }}</td>
                                <td class="text-enterprise-600">{{ $user->email }}</td>
                                <td class="text-enterprise-600">{{ $user->roleAssignments->pluck('role.name')->join(', ') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-10 text-center text-enterprise-500">No platform users on record.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-enterprise-200 px-4 py-3">
                {{ $users->links() }}
            </div>
        </div>

        <div class="panel">
            @if ($canManageUsers)
                <div class="panel-header">
                    <h2 class="panel-title">Add platform user</h2>
                </div>
                <form method="POST" action="{{ route('platform.users.store') }}" class="panel-body space-y-4">
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
                        <x-input-label for="role_id" value="Platform role" />
                        <select id="role_id" name="role_id" class="mt-1 block w-full">
                            @foreach ($platformRoles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                    </div>
                    <x-primary-button>Create account</x-primary-button>
                </form>
            @else
                <div class="panel-body text-sm text-enterprise-600">
                    You can view staff accounts here. Creating platform users requires the platform user management permission.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
