<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\PlatformRole;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_super_admin_can_view_platform_clients(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(PlatformRole::SuperAdmin);

        $this->actingAs($user)
            ->get(route('platform.clients.index'))
            ->assertOk()
            ->assertSee('Clients');
    }

    public function test_platform_user_can_masquerade_as_client_user(): void
    {
        $platformUser = User::factory()->create(['email_verified_at' => now()]);
        $platformUser->assignRole(PlatformRole::Admin);

        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);
        $clientUser = User::factory()->create(['email_verified_at' => now()]);
        $clientUser->assignRole(OrganizationRole::Recruiter, $organization);

        $this->actingAs($platformUser)
            ->post(route('platform.impersonation.store', $clientUser))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($clientUser);
        $this->assertTrue(session()->has('impersonator_id'));
    }

    public function test_platform_user_can_open_reports_area_when_organization_is_set(): void
    {
        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'current_organization_id' => $organization->id,
        ]);
        $user->assignRole(OrganizationRole::Recruiter, $organization);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Report requests')
            ->assertSee('No report requests submitted yet');
    }

    public function test_platform_user_can_exit_client_organization_view(): void
    {
        $platformUser = User::factory()->create(['email_verified_at' => now()]);
        $platformUser->assignRole(\App\Enums\PlatformRole::Admin);

        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);
        $platformUser->forceFill(['current_organization_id' => $organization->id])->save();
        session(['organization_id' => $organization->id]);

        $this->actingAs($platformUser)
            ->delete(route('organization.exit'))
            ->assertRedirect(route('platform.clients.index'));

        $platformUser->refresh();
        $this->assertNull($platformUser->current_organization_id);
        $this->assertFalse(session()->has('organization_id'));
    }
}
