<?php

namespace App\Http\Controllers\Platform;

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
    public function index(): View
    {
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

        return view('platform.users.index', compact('users', 'platformRoles'));
    }

    public function store(Request $request): RedirectResponse
    {
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
}
