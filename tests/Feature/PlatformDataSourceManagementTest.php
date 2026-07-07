<?php

namespace Tests\Feature;

use App\Enums\DataSourceDriver;
use App\Enums\PlatformRole;
use App\Models\DataSource;
use App\Models\User;
use Database\Seeders\InformDataDataSourceSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlatformDataSourceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_informdata_data_source_is_seeded(): void
    {
        $this->seed(InformDataDataSourceSeeder::class);

        $dataSource = DataSource::query()->where('slug', 'informdata-monitoring')->first();

        $this->assertNotNull($dataSource);
        $this->assertSame(DataSourceDriver::InformData->value, $dataSource->driver);
        $this->assertSame('https://api-monitoring.informdata.com/', $dataSource->documentation_url);
        $this->assertContains('continuous_monitoring', $dataSource->capabilities);
        $this->assertTrue($dataSource->needsConfiguration());
        $this->assertFalse($dataSource->is_active);
        $this->assertNull($dataSource->config);
    }

    public function test_platform_admin_can_configure_seeded_informdata_connection(): void
    {
        $this->seed(InformDataDataSourceSeeder::class);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(PlatformRole::Admin);

        $dataSource = DataSource::query()->where('slug', 'informdata-monitoring')->firstOrFail();

        $this->actingAs($user)
            ->patch(route('platform.data-sources.update', $dataSource), [
                'name' => $dataSource->name,
                'slug' => $dataSource->slug,
                'base_url' => 'https://informdata.example',
                'documentation_url' => $dataSource->documentation_url,
                'description' => $dataSource->description,
                'username' => 'api-user',
                'password' => 'secret-pass',
                'is_active' => true,
            ])
            ->assertRedirect(route('platform.data-sources.show', $dataSource));

        $dataSource->refresh();

        $this->assertFalse($dataSource->needsConfiguration());
        $this->assertTrue($dataSource->is_active);
        $this->assertSame('api-user', $dataSource->config['username']);
        $this->assertSame('https://informdata.example', $dataSource->base_url);
    }

    public function test_seeder_does_not_overwrite_existing_data_source_credentials(): void
    {
        DataSource::query()->create([
            'name' => 'InformData Continuous Monitoring',
            'slug' => 'informdata-monitoring',
            'driver' => DataSourceDriver::InformData->value,
            'base_url' => 'https://production.informdata.example',
            'documentation_url' => 'https://api-monitoring.informdata.com/',
            'capabilities' => DataSourceDriver::InformData->defaultCapabilities(),
            'config' => [
                'username' => 'saved-user',
                'password' => 'saved-pass',
            ],
            'is_active' => true,
        ]);

        $this->seed(InformDataDataSourceSeeder::class);

        $dataSource = DataSource::query()->where('slug', 'informdata-monitoring')->firstOrFail();

        $this->assertSame('https://production.informdata.example', $dataSource->base_url);
        $this->assertSame('saved-user', $dataSource->config['username']);
        $this->assertTrue($dataSource->is_active);
    }

    public function test_platform_admin_can_create_data_source(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(PlatformRole::Admin);

        $response = $this->actingAs($user)->post(route('platform.data-sources.store'), [
            'name' => 'InformData Sandbox',
            'slug' => 'informdata-sandbox',
            'driver' => DataSourceDriver::InformData->value,
            'base_url' => 'https://sandbox.informdata.example',
            'documentation_url' => 'https://api-monitoring.informdata.com/',
            'description' => 'Sandbox InformData connection.',
            'username' => 'api-user',
            'password' => 'secret-pass',
        ]);

        $dataSource = DataSource::query()->where('slug', 'informdata-sandbox')->first();

        $response->assertRedirect(route('platform.data-sources.show', $dataSource));
        $this->assertNotNull($dataSource);
        $this->assertSame('api-user', $dataSource->config['username']);
    }

    public function test_support_user_cannot_create_data_source(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(PlatformRole::Support);

        $this->actingAs($user)
            ->get(route('platform.data-sources.create'))
            ->assertForbidden();
    }

    public function test_platform_admin_can_test_informdata_connection(): void
    {
        Http::fake([
            'https://informdata.example/token' => Http::response(['access_token' => 'test-token'], 200),
            'https://informdata.example/api/IntegrationApi/GetCompanyProfiles' => Http::response(['profiles' => []], 200),
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(PlatformRole::Admin);

        $dataSource = DataSource::query()->create([
            'name' => 'InformData',
            'slug' => 'informdata-test',
            'driver' => DataSourceDriver::InformData->value,
            'base_url' => 'https://informdata.example',
            'documentation_url' => 'https://api-monitoring.informdata.com/',
            'capabilities' => DataSourceDriver::InformData->defaultCapabilities(),
            'config' => [
                'username' => 'api-user',
                'password' => 'secret-pass',
            ],
        ]);

        $this->actingAs($user)
            ->post(route('platform.data-sources.test', $dataSource))
            ->assertRedirect()
            ->assertSessionHas('status');

        $dataSource->refresh();

        $this->assertSame('ok', $dataSource->last_connection_status);
        $this->assertNotNull($dataSource->last_connected_at);
    }

    public function test_data_sources_index_shows_new_action_for_authorized_users(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(PlatformRole::Admin);

        $support = User::factory()->create(['email_verified_at' => now()]);
        $support->assignRole(PlatformRole::Support);

        $this->seed(InformDataDataSourceSeeder::class);

        $this->actingAs($admin)
            ->get(route('platform.data-sources.index'))
            ->assertOk()
            ->assertSee('InformData Continuous Monitoring')
            ->assertSee('Configure')
            ->assertSee('New data source');

        $this->actingAs($support)
            ->get(route('platform.data-sources.index'))
            ->assertOk()
            ->assertSee('InformData Continuous Monitoring')
            ->assertDontSee('New data source');
    }
}
