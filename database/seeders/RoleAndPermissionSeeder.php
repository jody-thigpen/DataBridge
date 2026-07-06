<?php

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Enums\Permission as PermissionEnum;
use App\Enums\PlatformRole;
use App\Enums\RoleScope;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private array $permissionLabels = [
        'platform.organizations.manage' => 'Manage client organizations',
        'platform.users.manage' => 'Manage platform users',
        'platform.settings.manage' => 'Manage platform settings',
        'platform.audit.view' => 'View platform audit logs',
        'org.users.manage' => 'Manage organization users',
        'org.users.invite' => 'Invite organization users',
        'org.orders.create' => 'Create screening orders',
        'org.orders.view' => 'View screening orders',
        'org.orders.view_all' => 'View all organization orders',
        'org.reports.view' => 'View screening reports',
        'org.reports.view_pii' => 'View secured report data',
        'org.billing.manage' => 'Manage organization billing',
        'org.settings.manage' => 'Manage organization settings',
    ];

    public function run(): void
    {
        $permissions = $this->seedPermissions();
        $this->seedPlatformRoles($permissions);
        $this->seedOrganizationRoles($permissions);
        $this->seedSuperAdmin();
    }

    /**
     * @return array<string, Permission>
     */
    private function seedPermissions(): array
    {
        $permissions = [];

        foreach (PermissionEnum::cases() as $permission) {
            $permissions[$permission->value] = Permission::query()->updateOrCreate(
                ['slug' => $permission->value],
                [
                    'name' => $this->permissionLabels[$permission->value],
                    'description' => $this->permissionLabels[$permission->value],
                ],
            );
        }

        return $permissions;
    }

    /**
     * @param  array<string, Permission>  $permissions
     */
    private function seedPlatformRoles(array $permissions): void
    {
        $definitions = [
            PlatformRole::SuperAdmin->value => [
                'name' => 'Super Admin',
                'description' => 'Full platform access',
                'sort_order' => 10,
                'permissions' => array_keys($permissions),
            ],
            PlatformRole::Admin->value => [
                'name' => 'Platform Admin',
                'description' => 'Manage organizations and platform users',
                'sort_order' => 20,
                'permissions' => [
                    PermissionEnum::PlatformOrganizationsManage->value,
                    PermissionEnum::PlatformUsersManage->value,
                    PermissionEnum::PlatformSettingsManage->value,
                    PermissionEnum::PlatformAuditView->value,
                ],
            ],
            PlatformRole::Operations->value => [
                'name' => 'Operations',
                'description' => 'Day-to-day screening operations',
                'sort_order' => 30,
                'permissions' => [
                    PermissionEnum::PlatformOrganizationsManage->value,
                    PermissionEnum::PlatformAuditView->value,
                ],
            ],
            PlatformRole::Support->value => [
                'name' => 'Support',
                'description' => 'Client support access',
                'sort_order' => 40,
                'permissions' => [
                    PermissionEnum::PlatformUsersManage->value,
                    PermissionEnum::PlatformAuditView->value,
                ],
            ],
        ];

        foreach ($definitions as $slug => $definition) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'scope' => RoleScope::Platform,
                    'description' => $definition['description'],
                    'sort_order' => $definition['sort_order'],
                ],
            );

            $role->permissions()->sync(
                collect($definition['permissions'])
                    ->map(fn (string $permissionSlug) => $permissions[$permissionSlug]->id)
                    ->all(),
            );
        }
    }

    /**
     * @param  array<string, Permission>  $permissions
     */
    private function seedOrganizationRoles(array $permissions): void
    {
        $orgPermissionSlugs = collect($permissions)
            ->keys()
            ->filter(fn (string $slug) => str_starts_with($slug, 'org.'))
            ->values()
            ->all();

        $definitions = [
            OrganizationRole::ClientAdmin->value => [
                'name' => 'Client Admin',
                'description' => 'Full access within the client organization',
                'sort_order' => 10,
                'permissions' => $orgPermissionSlugs,
            ],
            OrganizationRole::HrManager->value => [
                'name' => 'HR Manager',
                'description' => 'Manage users and view all orders and reports',
                'sort_order' => 20,
                'permissions' => [
                    PermissionEnum::OrgUsersInvite->value,
                    PermissionEnum::OrgOrdersCreate->value,
                    PermissionEnum::OrgOrdersView->value,
                    PermissionEnum::OrgOrdersViewAll->value,
                    PermissionEnum::OrgReportsView->value,
                    PermissionEnum::OrgReportsViewPii->value,
                    PermissionEnum::OrgSettingsManage->value,
                ],
            ],
            OrganizationRole::Recruiter->value => [
                'name' => 'Recruiter',
                'description' => 'Submit and track own screening orders',
                'sort_order' => 30,
                'permissions' => [
                    PermissionEnum::OrgOrdersCreate->value,
                    PermissionEnum::OrgOrdersView->value,
                    PermissionEnum::OrgReportsView->value,
                ],
            ],
            OrganizationRole::Viewer->value => [
                'name' => 'Viewer',
                'description' => 'Read-only access to orders and reports',
                'sort_order' => 40,
                'permissions' => [
                    PermissionEnum::OrgOrdersView->value,
                    PermissionEnum::OrgReportsView->value,
                ],
            ],
        ];

        foreach ($definitions as $slug => $definition) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'scope' => RoleScope::Organization,
                    'description' => $definition['description'],
                    'sort_order' => $definition['sort_order'],
                ],
            );

            $role->permissions()->sync(
                collect($definition['permissions'])
                    ->map(fn (string $permissionSlug) => $permissions[$permissionSlug]->id)
                    ->all(),
            );
        }
    }

    private function seedSuperAdmin(): void
    {
        $email = env('SUPER_ADMIN_EMAIL');

        if (blank($email)) {
            return;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ],
        );

        $user->assignRole(PlatformRole::SuperAdmin);
    }
}
