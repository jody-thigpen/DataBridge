@php
    $selectedRoleId = (string) old('role_id', $currentRoleId);
    $rolePermissions = $roles->mapWithKeys(fn ($role) => [
        (string) $role->id => $role->permissions->pluck('name')->values()->all(),
    ]);
@endphp

<div class="grid gap-5 lg:grid-cols-2">
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">Account details</h2>
        </div>
        <div class="panel-body space-y-4">
            <div>
                <x-input-label for="name" value="Full name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $user->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="email" value="Work email" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password" value="New password" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                <p class="mt-1 text-xs text-enterprise-500">Leave blank to keep the current password.</p>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-enterprise-300 text-brand-600 focus:ring-brand-500" @checked(old('is_active', $user->is_active))>
                <x-input-label for="is_active" value="Active account" class="mb-0" />
            </div>
        </div>
    </div>

    <div
        class="panel"
        x-data="{
            roles: @js($rolePermissions),
            selectedRoleId: @js($selectedRoleId),
            get permissions() {
                return this.roles[this.selectedRoleId] ?? [];
            }
        }"
    >
        <div class="panel-header">
            <h2 class="panel-title">Role &amp; permissions</h2>
        </div>
        <div class="panel-body space-y-4">
            @if ($user->isSuperAdmin())
                <p class="text-sm text-enterprise-600">
                    This account is a Super Admin. The role cannot be changed here.
                </p>
            @else
                <div>
                    <x-input-label for="role_id" :value="$roleLabel ?? 'Role'" />
                    <select
                        id="role_id"
                        name="role_id"
                        class="mt-1 block w-full"
                        x-model="selectedRoleId"
                        required
                    >
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                </div>
            @endif

            <div>
                <p class="text-sm font-medium text-enterprise-800">Included permissions</p>
                <p class="mt-1 text-sm text-enterprise-600">
                    Permissions are granted through the assigned role.
                </p>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-enterprise-700" x-show="permissions.length > 0">
                    <template x-for="permission in permissions" :key="permission">
                        <li x-text="permission"></li>
                    </template>
                </ul>
                <p class="mt-3 text-sm text-enterprise-500" x-show="permissions.length === 0">
                    @if ($user->isSuperAdmin())
                        Super Admin includes all platform and organization permissions.
                    @else
                        Select a role to view its permissions.
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>
