<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Enums\SearchTypeCode;
use App\Models\DataSource;
use App\Models\Organization;
use App\Models\ScreeningPackage;
use App\Models\SearchType;
use App\Models\User;
use Database\Seeders\InformDataDataSourceSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SearchTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(InformDataDataSourceSeeder::class);
        $this->seed(SearchTypeSeeder::class);
    }

    public function test_search_types_are_seeded_for_informdata(): void
    {
        $this->assertDatabaseCount('search_types', count(SearchTypeCode::cases()));

        foreach (SearchTypeCode::cases() as $code) {
            $this->assertDatabaseHas('search_types', ['code' => $code->value]);
        }
    }

    public function test_platform_admin_can_create_package_with_searches_and_data_sources(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(PlatformRole::Admin);

        $county = SearchType::query()->where('code', SearchTypeCode::CountyCriminal->value)->firstOrFail();
        $national = SearchType::query()->where('code', SearchTypeCode::NationalCriminal->value)->firstOrFail();
        $dataSource = DataSource::query()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('platform.packages.store'), [
                'name' => 'Standard Criminal Package',
                'slug' => 'standard-criminal-package',
                'base_price' => '49.95',
                'description' => 'County and national criminal searches.',
                'items' => [
                    ['search_type_id' => $county->id, 'data_source_id' => $dataSource->id],
                    ['search_type_id' => $national->id, 'data_source_id' => $dataSource->id],
                ],
            ])
            ->assertRedirect();

        $package = ScreeningPackage::query()->where('slug', 'standard-criminal-package')->first();

        $this->assertNotNull($package);
        $this->assertSame('49.95', $package->base_price);
        $this->assertCount(2, $package->searchTypes);
    }

    public function test_platform_admin_can_assign_package_to_client_with_price_override(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(PlatformRole::Admin);

        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);
        $package = ScreeningPackage::query()->create([
            'name' => 'Basic Package',
            'slug' => 'basic-package',
            'base_price' => 40.00,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('platform.clients.package-prices.update', $organization), [
                'packages' => [
                    ['screening_package_id' => $package->id, 'assigned' => '1', 'price' => '35.00'],
                ],
            ])
            ->assertRedirect();

        $this->assertTrue($package->fresh()->isAssignedTo($organization));
        $this->assertSame('35.00', $package->fresh()->priceForOrganization($organization));
    }

    public function test_package_can_be_assigned_to_multiple_clients_with_different_prices(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(PlatformRole::Admin);

        $clientA = Organization::query()->create(['name' => 'Client A', 'slug' => 'client-a']);
        $clientB = Organization::query()->create(['name' => 'Client B', 'slug' => 'client-b']);
        $package = ScreeningPackage::query()->create([
            'name' => 'Shared Package',
            'slug' => 'shared-package',
            'base_price' => 50.00,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('platform.clients.package-prices.update', $clientA), [
                'packages' => [
                    ['screening_package_id' => $package->id, 'assigned' => '1', 'price' => '45.00'],
                ],
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->patch(route('platform.clients.package-prices.update', $clientB), [
                'packages' => [
                    ['screening_package_id' => $package->id, 'assigned' => '1'],
                ],
            ])
            ->assertRedirect();

        $this->assertSame('45.00', $package->fresh()->priceForOrganization($clientA));
        $this->assertSame('50.00', $package->fresh()->priceForOrganization($clientB));
        $this->assertCount(2, $package->fresh()->organizations);
    }

    public function test_unassigning_package_removes_client_price_override(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(PlatformRole::Admin);

        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);
        $package = ScreeningPackage::query()->create([
            'name' => 'Basic Package',
            'slug' => 'basic-package',
            'base_price' => 40.00,
            'is_active' => true,
        ]);

        $organization->screeningPackages()->attach($package->id);
        $package->organizationPrices()->create([
            'organization_id' => $organization->id,
            'price' => 35.00,
        ]);

        $this->actingAs($admin)
            ->patch(route('platform.clients.package-prices.update', $organization), [
                'packages' => [
                    ['screening_package_id' => $package->id],
                ],
            ])
            ->assertRedirect();

        $this->assertFalse($package->fresh()->isAssignedTo($organization));
        $this->assertDatabaseMissing('organization_package_prices', [
            'organization_id' => $organization->id,
            'screening_package_id' => $package->id,
        ]);
    }

    public function test_support_user_cannot_manage_catalog(): void
    {
        $support = User::factory()->create(['email_verified_at' => now()]);
        $support->assignRole(PlatformRole::Support);

        $this->actingAs($support)
            ->get(route('platform.search-types.create'))
            ->assertForbidden();

        $this->actingAs($support)
            ->get(route('platform.packages.create'))
            ->assertForbidden();
    }

    public function test_report_request_form_lists_only_assigned_packages_with_client_price(): void
    {
        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'current_organization_id' => $organization->id,
        ]);
        $user->assignRole(\App\Enums\OrganizationRole::Recruiter, $organization);

        session(['organization_id' => $organization->id]);

        $assignedPackage = ScreeningPackage::query()->create([
            'name' => 'Executive Package',
            'slug' => 'executive-package',
            'base_price' => 99.00,
            'is_active' => true,
        ]);

        $unassignedPackage = ScreeningPackage::query()->create([
            'name' => 'Internal Package',
            'slug' => 'internal-package',
            'base_price' => 25.00,
            'is_active' => true,
        ]);

        $organization->screeningPackages()->attach($assignedPackage->id);

        $this->actingAs($user)
            ->get(route('reports.requests.create'))
            ->assertOk()
            ->assertSee('Executive Package')
            ->assertSee('$99.00')
            ->assertDontSee('Internal Package');
    }
}
