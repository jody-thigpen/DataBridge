<?php

namespace App\Http\Controllers\Organization;

use App\Enums\OrganizationRole;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request, OrganizationContext $organizationContext): View
    {
        $organization = $organizationContext->current();
        $user = $request->user();

        abort_unless(
            $user->hasPermission(Permission::OrgUsersManage, $organization)
            || $user->hasPermission(Permission::OrgUsersInvite, $organization),
            403,
        );

        $canManageUsers = $user->hasPermission(Permission::OrgUsersManage, $organization);
        $canAddUsers = $canManageUsers || $user->hasPermission(Permission::OrgUsersInvite, $organization);

        $users = User::query()
            ->whereHas('roleAssignments', fn ($query) => $query->where('organization_id', $organization->id))
            ->with(['roleAssignments' => fn ($query) => $query->where('organization_id', $organization->id)->with('role')])
            ->orderBy('name')
            ->paginate(20);

        $organizationRoles = Role::query()
            ->where('scope', 'organization')
            ->orderBy('sort_order')
            ->get();

        return view('organization.users.index', compact('organization', 'users', 'organizationRoles', 'canManageUsers', 'canAddUsers'));
    }

    public function store(Request $request, OrganizationContext $organizationContext): RedirectResponse
    {
        $organization = $organizationContext->current();
        $user = $request->user();

        abort_unless(
            $user->hasPermission(Permission::OrgUsersManage, $organization)
            || $user->hasPermission(Permission::OrgUsersInvite, $organization),
            403,
        );

        $organizationRoleIds = Role::query()->where('scope', 'organization')->pluck('id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role_id' => ['required', Rule::in($organizationRoleIds)],
        ]);

        if (! $user->hasPermission(Permission::OrgUsersManage, $organization)
            && Role::query()->find($validated['role_id'])?->slug === OrganizationRole::ClientAdmin->value) {
            return back()->withErrors(['role_id' => 'You cannot invite another client admin.']);
        }

        $newUser = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'current_organization_id' => $organization->id,
        ]);

        $role = Role::query()->findOrFail($validated['role_id']);
        $newUser->assignRole($role, $organization);

        return back()->with('status', "{$newUser->name} added to {$organization->name}.");
    }
}
