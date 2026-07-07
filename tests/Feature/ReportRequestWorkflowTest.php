<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\PlatformRole;
use App\Enums\ReportRequestStatus;
use App\Models\Organization;
use App\Models\OrganizationSearchTypeSetting;
use App\Models\ReportRequest;
use App\Models\ScreeningPackage;
use App\Models\SearchType;
use App\Models\User;
use Database\Seeders\InformDataDataSourceSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SearchTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(InformDataDataSourceSeeder::class);
        $this->seed(SearchTypeSeeder::class);
    }

    public function test_client_request_requiring_review_is_queued_for_saffhire(): void
    {
        [$organization, $package, $user] = $this->clientContext();

        $searchType = $package->searchTypes()->firstOrFail();
        $searchType->update(['requires_review_before_submit' => true]);

        session(['organization_id' => $organization->id]);

        $this->actingAs($user)
            ->post(route('reports.requests.store'), [
                'subject_name' => 'Jane Candidate',
                'screening_package_id' => $package->id,
                'notes' => 'Urgent hire',
            ])
            ->assertRedirect(route('reports.index'));

        $request = ReportRequest::query()->first();

        $this->assertNotNull($request);
        $this->assertTrue($request->requires_review);
        $this->assertSame(ReportRequestStatus::PendingReview, $request->status);
        $this->assertNull($request->submitted_at);
    }

    public function test_client_request_with_auto_submit_search_is_submitted_immediately(): void
    {
        [$organization, $package, $user] = $this->clientContext();

        foreach ($package->searchTypes as $searchType) {
            $searchType->update(['requires_review_before_submit' => false]);
        }

        session(['organization_id' => $organization->id]);

        $this->actingAs($user)
            ->post(route('reports.requests.store'), [
                'subject_name' => 'John Candidate',
                'screening_package_id' => $package->id,
            ])
            ->assertRedirect(route('reports.index'));

        $request = ReportRequest::query()->first();

        $this->assertFalse($request->requires_review);
        $this->assertSame(ReportRequestStatus::Submitted, $request->status);
        $this->assertNotNull($request->submitted_at);
    }

    public function test_client_override_can_force_auto_submit_for_reviewable_search(): void
    {
        [$organization, $package, $user] = $this->clientContext();

        $searchType = $package->searchTypes()->firstOrFail();
        $searchType->update(['requires_review_before_submit' => true]);

        OrganizationSearchTypeSetting::query()->create([
            'organization_id' => $organization->id,
            'search_type_id' => $searchType->id,
            'requires_review_before_submit' => false,
        ]);

        session(['organization_id' => $organization->id]);

        $this->actingAs($user)
            ->post(route('reports.requests.store'), [
                'subject_name' => 'Alex Candidate',
                'screening_package_id' => $package->id,
            ])
            ->assertRedirect(route('reports.index'));

        $request = ReportRequest::query()->first();

        $this->assertFalse($request->requires_review);
        $this->assertSame(ReportRequestStatus::Submitted, $request->status);
    }

    public function test_platform_operations_user_can_filter_and_assign_report_request(): void
    {
        [$organization, $package, $user] = $this->clientContext();
        $searchType = $package->searchTypes()->firstOrFail();
        $searchType->update(['requires_review_before_submit' => true]);

        session(['organization_id' => $organization->id]);

        $this->actingAs($user)->post(route('reports.requests.store'), [
            'subject_name' => 'Taylor Candidate',
            'screening_package_id' => $package->id,
        ]);

        $reportRequest = ReportRequest::query()->firstOrFail();
        $operationsUser = User::factory()->create(['email_verified_at' => now()]);
        $operationsUser->assignRole(PlatformRole::Operations);
        $reviewer = User::factory()->create(['email_verified_at' => now(), 'name' => 'Ops Reviewer']);
        $reviewer->assignRole(PlatformRole::Admin);

        $this->actingAs($operationsUser)
            ->get(route('platform.report-requests.index', ['organization_id' => $organization->id, 'q' => 'Taylor']))
            ->assertOk()
            ->assertSee('Taylor Candidate')
            ->assertSee($organization->name);

        $this->actingAs($operationsUser)
            ->patch(route('platform.report-requests.assign', $reportRequest), [
                'assigned_to_user_id' => $reviewer->id,
            ])
            ->assertRedirect();

        $reportRequest->refresh();

        $this->assertSame(ReportRequestStatus::Assigned, $reportRequest->status);
        $this->assertSame($reviewer->id, $reportRequest->assigned_to_user_id);
    }

    public function test_platform_user_can_approve_pending_request(): void
    {
        [$organization, $package, $user] = $this->clientContext();
        $package->searchTypes()->firstOrFail()->update(['requires_review_before_submit' => true]);

        session(['organization_id' => $organization->id]);
        $this->actingAs($user)->post(route('reports.requests.store'), [
            'subject_name' => 'Sam Candidate',
            'screening_package_id' => $package->id,
        ]);

        $reportRequest = ReportRequest::query()->firstOrFail();
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(PlatformRole::Admin);

        $this->actingAs($admin)
            ->patch(route('platform.report-requests.approve', $reportRequest), [
                'review_notes' => 'Looks good',
            ])
            ->assertRedirect();

        $reportRequest->refresh();

        $this->assertSame(ReportRequestStatus::Submitted, $reportRequest->status);
        $this->assertNotNull($reportRequest->submitted_at);
        $this->assertSame('Looks good', $reportRequest->review_notes);
    }

    /**
     * @return array{0: Organization, 1: ScreeningPackage, 2: User}
     */
    private function clientContext(): array
    {
        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'current_organization_id' => $organization->id,
        ]);
        $user->assignRole(OrganizationRole::Recruiter, $organization);

        $searchType = SearchType::query()->firstOrFail();
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

        return [$organization, $package->fresh(['searchTypes']), $user];
    }
}
