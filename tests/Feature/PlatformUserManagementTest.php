<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\Permission;
use App\Enums\PlatformRole;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_platform_admin_can_update_platform_user_details_and_role(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(PlatformRole::Admin);

        $target = User::factory()->create([
            'email_verified_at' => now(),
            'name' => 'Support Staff',
            'email' => 'support@example.test',
        ]);
        $target->assignRole(PlatformRole::Support);

        $operationsRoleId = Role::query()->where('slug', PlatformRole::Operations->value)->value('id');

        $this->actingAs($admin)
            ->patch(route('platform.users.update', $target), [
                'name' => 'Operations Staff',
                'email' => 'operations@example.test',
                'role_id' => $operationsRoleId,
                'is_active' => '1',
            ])
            ->assertRedirect(route('platform.users.index'));

        $target->refresh();

        $this->assertSame('Operations Staff', $target->name);
        $this->assertSame('operations@example.test', $target->email);
        $this->assertTrue($target->hasPlatformRole(PlatformRole::Operations));
        $this->assertFalse($target->hasPlatformRole(PlatformRole::Support));
    }

    public function test_platform_user_without_manage_permission_cannot_edit_users(): void
    {
        $operations = User::factory()->create(['email_verified_at' => now()]);
        $operations->assignRole(PlatformRole::Operations);

        $target = User::factory()->create(['email_verified_at' => now()]);
        $target->assignRole(PlatformRole::Support);

        $this->actingAs($operations)
            ->get(route('platform.users.edit', $target))
            ->assertForbidden();

        $this->actingAs($operations)
            ->patch(route('platform.users.update', $target), [
                'name' => 'Blocked',
                'email' => $target->email,
                'role_id' => Role::query()->where('slug', PlatformRole::Support->value)->value('id'),
                'is_active' => '1',
            ])
            ->assertForbidden();
    }

    public function test_non_super_admin_cannot_edit_super_admin_account(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(PlatformRole::Admin);

        $superAdmin = User::factory()->create(['email_verified_at' => now()]);
        $superAdmin->assignRole(PlatformRole::SuperAdmin);

        $this->actingAs($admin)
            ->get(route('platform.users.edit', $superAdmin))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('platform.users.update', $superAdmin), [
                'name' => 'Hacked',
                'email' => $superAdmin->email,
                'role_id' => Role::query()->where('slug', PlatformRole::Admin->value)->value('id'),
                'is_active' => '1',
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_update_super_admin_profile_without_changing_role(): void
    {
        $superAdmin = User::factory()->create([
            'email_verified_at' => now(),
            'name' => 'Root Admin',
            'email' => 'root@example.test',
        ]);
        $superAdmin->assignRole(PlatformRole::SuperAdmin);

        $this->actingAs($superAdmin)
            ->patch(route('platform.users.update', $superAdmin), [
                'name' => 'Root Admin Updated',
                'email' => 'root-updated@example.test',
                'role_id' => Role::query()->where('slug', PlatformRole::SuperAdmin->value)->value('id'),
                'is_active' => '1',
            ])
            ->assertRedirect(route('platform.users.index'));

        $superAdmin->refresh();

        $this->assertSame('Root Admin Updated', $superAdmin->name);
        $this->assertTrue($superAdmin->isSuperAdmin());
    }

    public function test_client_admin_can_update_organization_user_role(): void
    {
        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);

        $clientAdmin = User::factory()->create([
            'email_verified_at' => now(),
            'current_organization_id' => $organization->id,
        ]);
        $clientAdmin->assignRole(OrganizationRole::ClientAdmin, $organization);

        $employee = User::factory()->create([
            'email_verified_at' => now(),
            'current_organization_id' => $organization->id,
            'name' => 'Viewer User',
            'email' => 'viewer@client-co.test',
        ]);
        $employee->assignRole(OrganizationRole::Viewer, $organization);

        session(['organization_id' => $organization->id]);

        $recruiterRoleId = Role::query()->where('slug', OrganizationRole::Recruiter->value)->value('id');

        $this->actingAs($clientAdmin)
            ->patch(route('organization.users.update', $employee), [
                'name' => 'Recruiter User',
                'email' => 'recruiter@client-co.test',
                'role_id' => $recruiterRoleId,
                'is_active' => '1',
            ])
            ->assertRedirect(route('organization.users.index'));

        $employee->refresh();

        $this->assertSame('Recruiter User', $employee->name);
        $this->assertTrue($employee->hasOrganizationRole(OrganizationRole::Recruiter, $organization));
        $this->assertTrue($employee->hasPermission(Permission::OrgOrdersCreate, $organization));
    }

    public function test_invite_only_user_cannot_edit_organization_users(): void
    {
        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);

        $inviter = User::factory()->create([
            'email_verified_at' => now(),
            'current_organization_id' => $organization->id,
        ]);
        $inviter->assignRole(OrganizationRole::HrManager, $organization);

        $employee = User::factory()->create([
            'email_verified_at' => now(),
            'current_organization_id' => $organization->id,
        ]);
        $employee->assignRole(OrganizationRole::Viewer, $organization);

        session(['organization_id' => $organization->id]);

        $this->actingAs($inviter)
            ->get(route('organization.users.edit', $employee))
            ->assertForbidden();
    }

    public function test_platform_admin_can_update_client_organization_user_from_client_page(): void
    {
        $platformAdmin = User::factory()->create(['email_verified_at' => now()]);
        $platformAdmin->assignRole(PlatformRole::Admin);

        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);

        $employee = User::factory()->create([
            'email_verified_at' => now(),
            'current_organization_id' => $organization->id,
        ]);
        $employee->assignRole(OrganizationRole::Viewer, $organization);

        $hrRoleId = Role::query()->where('slug', OrganizationRole::HrManager->value)->value('id');

        $this->actingAs($platformAdmin)
            ->patch(route('platform.clients.users.update', [$organization, $employee]), [
                'name' => 'HR Lead',
                'email' => $employee->email,
                'role_id' => $hrRoleId,
                'is_active' => '1',
            ])
            ->assertRedirect(route('platform.clients.show', $organization));

        $employee->refresh();

        $this->assertSame('HR Lead', $employee->name);
        $this->assertTrue($employee->hasOrganizationRole(OrganizationRole::HrManager, $organization));
    }

    public function test_sync_platform_role_replaces_existing_platform_assignment(): void
    {
        $user = User::factory()->create();
        $user->assignRole(PlatformRole::Support);

        $operationsRole = Role::query()->where('slug', PlatformRole::Operations->value)->firstOrFail();
        $user->syncPlatformRole($operationsRole);

        $this->assertTrue($user->hasPlatformRole(PlatformRole::Operations));
        $this->assertFalse($user->hasPlatformRole(PlatformRole::Support));
        $this->assertSame(1, $user->roleAssignments()->whereNull('organization_id')->count());
    }
}
