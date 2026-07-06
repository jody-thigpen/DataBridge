<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\Permission;
use App\Enums\PlatformRole;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationUserRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_can_hold_different_roles_in_multiple_organizations(): void
    {
        $user = User::factory()->create();
        $recruitingFirm = Organization::query()->create(['name' => 'Acme Recruiting', 'slug' => 'acme-recruiting']);
        $clientCompany = Organization::query()->create(['name' => 'Beta Corp', 'slug' => 'beta-corp']);

        $user->assignRole(OrganizationRole::ClientAdmin, $recruitingFirm);
        $user->assignRole(OrganizationRole::Recruiter, $clientCompany);

        $this->assertTrue($user->hasOrganizationRole(OrganizationRole::ClientAdmin, $recruitingFirm));
        $this->assertTrue($user->hasOrganizationRole(OrganizationRole::Recruiter, $clientCompany));
        $this->assertFalse($user->hasOrganizationRole(OrganizationRole::ClientAdmin, $clientCompany));
        $this->assertCount(2, $user->accessibleOrganizations());
    }

    public function test_organization_permissions_are_scoped_to_the_active_organization(): void
    {
        $user = User::factory()->create();
        $organizationA = Organization::query()->create(['name' => 'Org A', 'slug' => 'org-a']);
        $organizationB = Organization::query()->create(['name' => 'Org B', 'slug' => 'org-b']);

        $user->assignRole(OrganizationRole::Viewer, $organizationA);
        $user->assignRole(OrganizationRole::ClientAdmin, $organizationB);

        $this->assertTrue($user->hasPermission(Permission::OrgOrdersView, $organizationA));
        $this->assertFalse($user->hasPermission(Permission::OrgUsersManage, $organizationA));
        $this->assertTrue($user->hasPermission(Permission::OrgUsersManage, $organizationB));
    }

    public function test_super_admin_bypasses_permission_checks(): void
    {
        $user = User::factory()->create();
        $user->assignRole(PlatformRole::SuperAdmin);

        $organization = Organization::query()->create(['name' => 'Org A', 'slug' => 'org-a']);

        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->hasPermission(Permission::OrgBillingManage, $organization));
        $this->assertTrue($user->hasPermission(Permission::PlatformSettingsManage));
    }

    public function test_platform_roles_cannot_be_assigned_to_an_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create(['name' => 'Org A', 'slug' => 'org-a']);

        $this->expectException(\InvalidArgumentException::class);

        $user->assignRole(PlatformRole::Admin, $organization);
    }

    public function test_organization_roles_require_an_organization(): void
    {
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $user->assignRole(OrganizationRole::Viewer);
    }

    public function test_organizations_can_have_parent_child_relationships(): void
    {
        $parent = Organization::query()->create(['name' => 'Parent Co', 'slug' => 'parent-co']);
        $child = Organization::query()->create([
            'name' => 'Subsidiary Co',
            'slug' => 'subsidiary-co',
            'parent_id' => $parent->id,
        ]);

        $this->assertTrue($parent->isAncestorOf($child));
        $this->assertSame($parent->id, $child->parent->id);
    }
}
