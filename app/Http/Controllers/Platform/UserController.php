<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Permission;
use App\Enums\PlatformRole;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $canManageUsers = $request->user()?->hasPermission(Permission::PlatformUsersManage) ?? false;

        $users = User::query()
            ->whereHas('roleAssignments', fn ($query) => $query->whereNull('organization_id'))
            ->with(['roleAssignments.role'])
            ->orderBy('name')
            ->paginate(20);

        $platformRoles = Role::query()
            ->where('scope', 'platform')
            ->where('slug', '!=', PlatformRole::SuperAdmin->value)
            ->orderBy('sort_order')
            ->get();

        return view('platform.users.index', compact('users', 'platformRoles', 'canManageUsers'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission(Permission::PlatformUsersManage), 403);

        $platformRoleIds = Role::query()
            ->where('scope', 'platform')
            ->where('slug', '!=', PlatformRole::SuperAdmin->value)
            ->pluck('id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role_id' => ['required', Rule::in($platformRoleIds)],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $role = Role::query()->findOrFail($validated['role_id']);
        $user->assignRole($role);

        return back()->with('status', "{$user->name} added as platform user.");
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensurePlatformUser($user);
        abort_unless($request->user()?->hasPermission(Permission::PlatformUsersManage), 403);
        $this->ensureCanManageTargetUser($request, $user);

        $platformRoles = $this->assignablePlatformRoles($user);
        $currentRoleId = $user->roleAssignments()
            ->whereNull('organization_id')
            ->value('role_id');

        return view('platform.users.edit', compact('user', 'platformRoles', 'currentRoleId'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensurePlatformUser($user);
        abort_unless($request->user()?->hasPermission(Permission::PlatformUsersManage), 403);
        $this->ensureCanManageTargetUser($request, $user);

        $platformRoleIds = $this->assignablePlatformRoles($user)->pluck('id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', Password::defaults()],
            'role_id' => ['required', Rule::in($platformRoleIds)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = now();
        }

        $user->save();

        if (! $user->isSuperAdmin()) {
            $role = Role::query()->findOrFail($validated['role_id']);
            $user->syncPlatformRole($role);
        }

        return redirect()
            ->route('platform.users.index')
            ->with('status', "{$user->name} updated.");
    }

    private function ensurePlatformUser(User $user): void
    {
        abort_unless($user->isPlatformUser(), 404);
    }

    private function ensureCanManageTargetUser(Request $request, User $target): void
    {
        if ($target->isSuperAdmin() && ! $request->user()?->isSuperAdmin()) {
            abort(403);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Role>
     */
    private function assignablePlatformRoles(User $user)
    {
        return Role::query()
            ->where('scope', 'platform')
            ->when(
                ! $user->isSuperAdmin(),
                fn ($query) => $query->where('slug', '!=', PlatformRole::SuperAdmin->value),
            )
            ->with('permissions')
            ->orderBy('sort_order')
            ->get();
    }
}
