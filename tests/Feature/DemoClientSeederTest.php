<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DemoClientSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoClientSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_client_admin_can_manage_organization_users(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(DemoClientSeeder::class);

        $admin = User::query()->where('email', 'demo-admin@demo-client.test')->firstOrFail();
        $organization = Organization::query()->where('slug', 'demo-client-co')->firstOrFail();

        $this->assertTrue($admin->hasOrganizationRole(OrganizationRole::ClientAdmin, $organization));
        $this->assertTrue($admin->hasPermission(\App\Enums\Permission::OrgUsersManage, $organization));
        $this->assertTrue($admin->hasPermission(\App\Enums\Permission::OrgUsersInvite, $organization));
    }
}
