<?php

namespace App\Http\Controllers\Platform;

use App\Enums\OrganizationRole;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Role;
use App\Models\ScreeningPackage;
use App\Models\SearchType;
use App\Models\User;
use App\Services\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $organizations = Organization::query()
            ->with('parent')
            ->withCount('users')
            ->orderBy('name')
            ->paginate(20);

        return view('platform.clients.index', [
            'organizations' => $organizations,
            'canManageClients' => $this->canManageClients($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->canManageClients($request->user()), 403);

        $parentOrganizations = Organization::query()
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $clientManagers = User::query()->clientManagers()->get();

        return view('platform.clients.create', compact('parentOrganizations', 'clientManagers'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canManageClients($request->user()), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:organizations,slug'],
            'parent_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'is_active' => ['sometimes', 'boolean'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', Password::defaults()],
            'client_manager_id' => ['nullable', Rule::in($this->clientManagerIds())],
        ]);

        $organization = DB::transaction(function () use ($validated): Organization {
            $organization = Organization::query()->create([
                'name' => $validated['name'],
                'slug' => $validated['slug'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'client_manager_id' => $validated['client_manager_id'] ?? null,
            ]);

            $admin = User::query()->create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
                'email_verified_at' => now(),
                'current_organization_id' => $organization->id,
            ]);

            $admin->assignRole(OrganizationRole::ClientAdmin, $organization);

            return $organization;
        });

        return redirect()
            ->route('platform.clients.show', $organization)
            ->with('status', "{$organization->name} created with an initial client admin account.");
    }

    public function show(Request $request, Organization $organization): View
    {
        $organization->load(['parent', 'children', 'clientManager', 'roleAssignments.user', 'roleAssignments.role']);

        $canManageClients = $this->canManageClients($request->user());

        $organizationRoles = $canManageClients
            ? Role::query()->where('scope', 'organization')->orderBy('sort_order')->get()
            : collect();

        $canManageCatalog = $request->user()?->hasPermission(Permission::PlatformCatalogManage) ?? false;

        $assignedPackageIds = $organization->screeningPackages()->pluck('screening_packages.id')->all();

        $allPackages = ScreeningPackage::query()
            ->with(['organizationPrices' => fn ($query) => $query->where('organization_id', $organization->id)])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $assignedPackages = $canManageCatalog
            ? $allPackages
            : $organization->screeningPackages()
                ->with(['organizationPrices' => fn ($query) => $query->where('organization_id', $organization->id)])
                ->where('screening_packages.is_active', true)
                ->orderBy('name')
                ->get();

        $searchTypes = SearchType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $searchTypeSettings = $organization->searchTypeSettings()
            ->get()
            ->keyBy('search_type_id');

        $clientManagers = User::query()->clientManagers()->get();

        return view('platform.clients.show', compact(
            'organization',
            'canManageClients',
            'canManageCatalog',
            'organizationRoles',
            'allPackages',
            'assignedPackageIds',
            'assignedPackages',
            'searchTypes',
            'searchTypeSettings',
            'clientManagers',
        ));
    }

    public function storeUser(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($this->canManageClients($request->user()), 403);

        $organizationRoleIds = Role::query()->where('scope', 'organization')->pluck('id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role_id' => ['required', Rule::in($organizationRoleIds)],
        ]);

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

    public function enter(Organization $organization, OrganizationContext $organizationContext): RedirectResponse
    {
        $organizationContext->set($organization);

        return redirect()->route('dashboard')->with('status', "Now viewing {$organization->name}.");
    }

    public function updateClientManager(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($this->canManageClients($request->user()), 403);

        $validated = $request->validate([
            'client_manager_id' => ['nullable', Rule::in($this->clientManagerIds())],
        ]);

        $organization->update([
            'client_manager_id' => $validated['client_manager_id'] ?? null,
        ]);

        return back()->with('status', 'Client manager updated.');
    }

    /**
     * @return list<int>
     */
    private function clientManagerIds(): array
    {
        return User::query()->clientManagers()->pluck('id')->all();
    }

    private function canManageClients(?User $user): bool
    {
        return $user !== null && $user->hasPermission(Permission::PlatformOrganizationsManage);
    }
}
