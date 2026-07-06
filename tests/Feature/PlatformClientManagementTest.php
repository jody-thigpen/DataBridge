<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\PlatformRole;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformClientManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_platform_admin_can_create_client_organization_with_initial_admin(): void
    {
        $platformUser = User::factory()->create(['email_verified_at' => now()]);
        $platformUser->assignRole(PlatformRole::Admin);

        $response = $this->actingAs($platformUser)->post(route('platform.clients.store'), [
            'name' => 'Acme Staffing',
            'admin_name' => 'Jane Admin',
            'admin_email' => 'jane@acme-staffing.test',
            'admin_password' => 'Password123!',
        ]);

        $organization = Organization::query()->where('slug', 'acme-staffing')->first();
        $admin = User::query()->where('email', 'jane@acme-staffing.test')->first();

        $response->assertRedirect(route('platform.clients.show', $organization));
        $this->assertNotNull($organization);
        $this->assertTrue($organization->is_active);
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasOrganizationRole(OrganizationRole::ClientAdmin, $organization));
        $this->assertSame($organization->id, $admin->current_organization_id);
    }

    public function test_platform_operations_user_can_create_client_organization(): void
    {
        $platformUser = User::factory()->create(['email_verified_at' => now()]);
        $platformUser->assignRole(PlatformRole::Operations);

        $this->actingAs($platformUser)
            ->get(route('platform.clients.create'))
            ->assertOk()
            ->assertSee('New client organization');
    }

    public function test_platform_support_user_cannot_create_client_organization(): void
    {
        $platformUser = User::factory()->create(['email_verified_at' => now()]);
        $platformUser->assignRole(PlatformRole::Support);

        $this->actingAs($platformUser)
            ->get(route('platform.clients.create'))
            ->assertForbidden();

        $this->actingAs($platformUser)
            ->post(route('platform.clients.store'), [
                'name' => 'Blocked Co',
                'admin_name' => 'Blocked Admin',
                'admin_email' => 'blocked@example.test',
                'admin_password' => 'Password123!',
            ])
            ->assertForbidden();
    }

    public function test_platform_admin_can_add_employee_to_existing_client(): void
    {
        $platformUser = User::factory()->create(['email_verified_at' => now()]);
        $platformUser->assignRole(PlatformRole::Admin);

        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);

        $this->actingAs($platformUser)
            ->post(route('platform.clients.users.store', $organization), [
                'name' => 'Recruiter One',
                'email' => 'recruiter@client-co.test',
                'password' => 'Password123!',
                'role_id' => Role::query()->where('slug', OrganizationRole::Recruiter->value)->value('id'),
            ])
            ->assertRedirect();

        $employee = User::query()->where('email', 'recruiter@client-co.test')->first();

        $this->assertNotNull($employee);
        $this->assertTrue($employee->hasOrganizationRole(OrganizationRole::Recruiter, $organization));
    }

    public function test_client_admin_can_add_team_member_in_their_organization(): void
    {
        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);
        $clientAdmin = User::factory()->create([
            'email_verified_at' => now(),
            'current_organization_id' => $organization->id,
        ]);
        $clientAdmin->assignRole(OrganizationRole::ClientAdmin, $organization);

        session(['organization_id' => $organization->id]);

        $this->actingAs($clientAdmin)
            ->post(route('organization.users.store'), [
                'name' => 'HR Manager',
                'email' => 'hr@client-co.test',
                'password' => 'Password123!',
                'role_id' => Role::query()->where('slug', OrganizationRole::HrManager->value)->value('id'),
            ])
            ->assertRedirect();

        $employee = User::query()->where('email', 'hr@client-co.test')->first();

        $this->assertNotNull($employee);
        $this->assertTrue($employee->hasOrganizationRole(OrganizationRole::HrManager, $organization));
    }

    public function test_client_index_shows_new_client_action_for_authorized_platform_users(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(PlatformRole::Admin);

        $support = User::factory()->create(['email_verified_at' => now()]);
        $support->assignRole(PlatformRole::Support);

        $this->actingAs($admin)
            ->get(route('platform.clients.index'))
            ->assertOk()
            ->assertSee('New client');

        $this->actingAs($support)
            ->get(route('platform.clients.index'))
            ->assertOk()
            ->assertDontSee('New client');
    }
}
