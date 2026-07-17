<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\PlatformRole;
use App\Enums\ReportOrderStatus;
use App\Models\Organization;
use App\Models\ReportOrder;
use App\Models\ScreeningPackage;
use App\Models\SearchType;
use App\Models\User;
use Database\Seeders\InformDataDataSourceSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SearchTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientManagerAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(InformDataDataSourceSeeder::class);
        $this->seed(SearchTypeSeeder::class);
        Mail::fake();
    }

    public function test_platform_admin_can_assign_client_manager_on_create(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(PlatformRole::Admin);

        $manager = User::factory()->create(['email_verified_at' => now(), 'name' => 'Casey Manager']);
        $manager->assignRole(PlatformRole::ClientManager);

        $this->actingAs($admin)
            ->post(route('platform.clients.store'), [
                'name' => 'Managed Client Co',
                'admin_name' => 'Client Admin',
                'admin_email' => 'admin@managed-client.test',
                'admin_password' => 'Password123!',
                'client_manager_id' => $manager->id,
            ])
            ->assertRedirect();

        $organization = Organization::query()->where('slug', 'managed-client-co')->firstOrFail();

        $this->assertSame($manager->id, $organization->client_manager_id);
    }

    public function test_platform_admin_can_update_client_manager_on_existing_client(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(PlatformRole::Admin);

        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);
        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole(PlatformRole::ClientManager);

        $this->actingAs($admin)
            ->patch(route('platform.clients.client-manager.update', $organization), [
                'client_manager_id' => $manager->id,
            ])
            ->assertRedirect();

        $this->assertSame($manager->id, $organization->fresh()->client_manager_id);
    }

    public function test_report_order_is_auto_assigned_to_client_manager_when_review_required(): void
    {
        [$organization, $package, $user, $manager] = $this->clientContextWithManager();
        app(\App\Services\CandidateFormQuestionDefaults::class)->seedForOrganization($organization);

        session(['organization_id' => $organization->id]);

        $this->actingAs($user)
            ->post(route('report-orders.store'), [
                'subject_name' => 'Jordan Applicant',
                'candidate_email' => 'jordan@example.test',
                'screening_package_id' => $package->id,
            ])
            ->assertRedirect(route('report-orders.index'));

        $reportOrder = ReportOrder::query()->firstOrFail();
        $this->assertSame(ReportOrderStatus::AwaitingCandidate, $reportOrder->status);

        $this->post(route('candidate.intake.store', $reportOrder->invite_token), [
            'answers' => [
                'legal_name' => 'Jordan Applicant',
                'date_of_birth' => '1990-01-15',
                'mobile_phone' => '555-0100',
                'other_names' => '',
                'address_history' => [[
                    'line1' => '123 Main St',
                    'line2' => '',
                    'city' => 'Austin',
                    'state' => 'TX',
                    'postal' => '78701',
                    'from' => '2020-01',
                    'to' => '',
                ]],
                'work_history' => [[
                    'employer' => 'Acme Corp',
                    'title' => 'Analyst',
                    'city' => 'Austin',
                    'state' => 'TX',
                    'from' => '2021-03',
                    'to' => '',
                    'reason_for_leaving' => '',
                ]],
            ],
            'authorization_accepted' => '1',
        ])->assertRedirect(route('candidate.intake.thanks'));

        $reportOrder->refresh();

        $this->assertSame(ReportOrderStatus::Assigned, $reportOrder->status);
        $this->assertSame($manager->id, $reportOrder->assigned_to_user_id);
        $this->assertNotNull($reportOrder->assigned_at);
    }

    public function test_report_order_can_be_reassigned_to_another_platform_user(): void
    {
        [$organization, $package, $user, $manager] = $this->clientContextWithManager();
        app(\App\Services\CandidateFormQuestionDefaults::class)->seedForOrganization($organization);

        session(['organization_id' => $organization->id]);

        $this->actingAs($user)->post(route('report-orders.store'), [
            'subject_name' => 'Jordan Applicant',
            'candidate_email' => 'jordan@example.test',
            'screening_package_id' => $package->id,
        ]);

        $reportOrder = ReportOrder::query()->firstOrFail();

        $this->post(route('candidate.intake.store', $reportOrder->invite_token), [
            'answers' => [
                'legal_name' => 'Jordan Applicant',
                'date_of_birth' => '1990-01-15',
                'mobile_phone' => '555-0100',
                'other_names' => '',
                'address_history' => [[
                    'line1' => '123 Main St',
                    'line2' => '',
                    'city' => 'Austin',
                    'state' => 'TX',
                    'postal' => '78701',
                    'from' => '2020-01',
                    'to' => '',
                ]],
                'work_history' => [[
                    'employer' => 'Acme Corp',
                    'title' => 'Analyst',
                    'city' => 'Austin',
                    'state' => 'TX',
                    'from' => '2021-03',
                    'to' => '',
                    'reason_for_leaving' => '',
                ]],
            ],
            'authorization_accepted' => '1',
        ]);

        $operationsUser = User::factory()->create(['email_verified_at' => now()]);
        $operationsUser->assignRole(PlatformRole::Operations);

        $this->actingAs($operationsUser)
            ->patch(route('platform.report-orders.assign', $reportOrder), [
                'assigned_to_user_id' => $operationsUser->id,
            ])
            ->assertRedirect();

        $reportOrder->refresh();

        $this->assertSame($operationsUser->id, $reportOrder->assigned_to_user_id);
    }

    /**
     * @return array{0: Organization, 1: ScreeningPackage, 2: User, 3: User}
     */
    private function clientContextWithManager(): array
    {
        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole(PlatformRole::ClientManager);

        $organization = Organization::query()->create([
            'name' => 'Client Co',
            'slug' => 'client-co',
            'client_manager_id' => $manager->id,
        ]);

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'current_organization_id' => $organization->id,
        ]);
        $user->assignRole(OrganizationRole::Recruiter, $organization);

        $searchType = SearchType::query()->firstOrFail();
        $searchType->update(['requires_review_before_submit' => true]);

        $package = ScreeningPackage::query()->create([
            'name' => 'Basic Package',
            'slug' => 'basic-package',
            'base_price' => 45.00,
            'is_active' => true,
        ]);
        $package->syncSearchItems([
            ['search_type_id' => $searchType->id, 'data_source_id' => $searchType->data_source_id],
        ]);
        $organization->screeningPackages()->attach($package->id);

        return [$organization, $package->fresh(['searchTypes']), $user, $manager];
    }
}
