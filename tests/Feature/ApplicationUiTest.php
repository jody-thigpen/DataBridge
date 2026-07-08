<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\PlatformRole;
use App\Models\Organization;
use App\Models\ReportRequest;
use App\Models\User;
use Database\Seeders\InformDataDataSourceSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SearchTypeSeeder;
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

    public function test_dashboard_shows_count_of_reports_awaiting_current_user_review(): void
    {
        $this->seed(InformDataDataSourceSeeder::class);
        $this->seed(SearchTypeSeeder::class);

        [$organization, $package, $clientUser] = $this->reportRequestContext();

        session(['organization_id' => $organization->id]);
        $this->actingAs($clientUser)->post(route('reports.requests.store'), [
            'subject_name' => 'Taylor Candidate',
            'screening_package_id' => $package->id,
        ]);

        $reviewer = User::factory()->create(['email_verified_at' => now(), 'name' => 'Ops Reviewer']);
        $reviewer->assignRole(PlatformRole::Admin);

        ReportRequest::query()->firstOrFail()->update([
            'assigned_to_user_id' => $reviewer->id,
            'status' => \App\Enums\ReportRequestStatus::Assigned,
        ]);

        $this->actingAs($reviewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Awaiting your review')
            ->assertSee('1')
            ->assertSee('1 report awaiting your review');
    }

    /**
     * @return array{0: Organization, 1: \App\Models\ScreeningPackage, 2: User}
     */
    private function reportRequestContext(): array
    {
        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);
        $clientUser = User::factory()->create([
            'email_verified_at' => now(),
            'current_organization_id' => $organization->id,
        ]);
        $clientUser->assignRole(OrganizationRole::Recruiter, $organization);

        $searchType = \App\Models\SearchType::query()->firstOrFail();
        $package = \App\Models\ScreeningPackage::query()->create([
            'name' => 'Basic Package',
            'slug' => 'basic-package',
            'base_price' => 45.00,
            'is_active' => true,
        ]);
        $package->syncSearchItems([
            ['search_type_id' => $searchType->id, 'data_source_id' => $searchType->data_source_id],
        ]);
        $organization->screeningPackages()->attach($package->id);
        $package->searchTypes()->firstOrFail()->update(['requires_review_before_submit' => true]);

        return [$organization, $package->fresh(['searchTypes']), $clientUser];
    }
}
