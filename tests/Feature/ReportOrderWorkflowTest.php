<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\PlatformRole;
use App\Enums\ReportOrderStatus;
use App\Mail\CandidateIntakeInvitation;
use App\Models\Organization;
use App\Models\OrganizationSearchTypeSetting;
use App\Models\ReportOrder;
use App\Models\SavedReportOrderFilter;
use App\Models\ScreeningPackage;
use App\Models\SearchType;
use App\Models\User;
use App\Services\CandidateFormQuestionDefaults;
use Database\Seeders\InformDataDataSourceSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SearchTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReportOrderWorkflowTest extends TestCase
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

    public function test_client_request_emails_candidate_and_awaits_intake(): void
    {
        [$organization, $package, $user] = $this->clientContext();

        $searchType = $package->searchTypes()->firstOrFail();
        $searchType->update(['requires_review_before_submit' => true]);

        session(['organization_id' => $organization->id]);

        $this->actingAs($user)
            ->post(route('report-orders.store'), [
                'subject_name' => 'Jane Candidate',
                'candidate_email' => 'jane@example.test',
                'screening_package_id' => $package->id,
                'notes' => 'Urgent hire',
            ])
            ->assertRedirect(route('report-orders.index'));

        $request = ReportOrder::query()->first();

        $this->assertNotNull($request);
        $this->assertTrue($request->requires_review);
        $this->assertSame(ReportOrderStatus::AwaitingCandidate, $request->status);
        $this->assertSame('jane@example.test', $request->candidate_email);
        $this->assertNotNull($request->invite_token);
        $this->assertNull($request->submitted_at);

        Mail::assertSent(CandidateIntakeInvitation::class, function (CandidateIntakeInvitation $mail) {
            return $mail->hasTo('jane@example.test');
        });
    }

    public function test_candidate_completion_moves_reviewable_request_to_pending_review(): void
    {
        [$organization, $package, $user] = $this->clientContext();
        $package->searchTypes()->firstOrFail()->update(['requires_review_before_submit' => true]);
        app(CandidateFormQuestionDefaults::class)->seedForOrganization($organization);

        session(['organization_id' => $organization->id]);
        $this->actingAs($user)->post(route('report-orders.store'), [
            'subject_name' => 'Jane Candidate',
            'candidate_email' => 'jane@example.test',
            'screening_package_id' => $package->id,
        ]);

        $reportOrder = ReportOrder::query()->firstOrFail();
        $this->completeCandidateIntake($reportOrder);

        $reportOrder->refresh();

        $this->assertSame(ReportOrderStatus::PendingReview, $reportOrder->status);
        $this->assertNotNull($reportOrder->candidate_completed_at);
        $this->assertNotNull($reportOrder->authorization_accepted_at);
        $this->assertNull($reportOrder->invite_token);
    }

    public function test_candidate_completion_auto_submits_when_review_not_required(): void
    {
        [$organization, $package, $user] = $this->clientContext();

        foreach ($package->searchTypes as $searchType) {
            $searchType->update(['requires_review_before_submit' => false]);
        }

        app(CandidateFormQuestionDefaults::class)->seedForOrganization($organization);

        session(['organization_id' => $organization->id]);
        $this->actingAs($user)->post(route('report-orders.store'), [
            'subject_name' => 'John Candidate',
            'candidate_email' => 'john@example.test',
            'screening_package_id' => $package->id,
        ]);

        $reportOrder = ReportOrder::query()->firstOrFail();
        $this->assertSame(ReportOrderStatus::AwaitingCandidate, $reportOrder->status);

        $this->completeCandidateIntake($reportOrder);
        $reportOrder->refresh();

        $this->assertFalse($reportOrder->requires_review);
        $this->assertSame(ReportOrderStatus::Submitted, $reportOrder->status);
        $this->assertNotNull($reportOrder->submitted_at);
    }

    public function test_client_override_can_force_auto_submit_after_candidate_intake(): void
    {
        [$organization, $package, $user] = $this->clientContext();

        $searchType = $package->searchTypes()->firstOrFail();
        $searchType->update(['requires_review_before_submit' => true]);

        OrganizationSearchTypeSetting::query()->create([
            'organization_id' => $organization->id,
            'search_type_id' => $searchType->id,
            'requires_review_before_submit' => false,
        ]);

        app(CandidateFormQuestionDefaults::class)->seedForOrganization($organization);

        session(['organization_id' => $organization->id]);
        $this->actingAs($user)->post(route('report-orders.store'), [
            'subject_name' => 'Alex Candidate',
            'candidate_email' => 'alex@example.test',
            'screening_package_id' => $package->id,
        ]);

        $reportOrder = ReportOrder::query()->firstOrFail();
        $this->completeCandidateIntake($reportOrder);
        $reportOrder->refresh();

        $this->assertFalse($reportOrder->requires_review);
        $this->assertSame(ReportOrderStatus::Submitted, $reportOrder->status);
    }

    public function test_platform_operations_user_can_filter_and_assign_report_order(): void
    {
        [$organization, $package, $user] = $this->clientContext();
        $searchType = $package->searchTypes()->firstOrFail();
        $searchType->update(['requires_review_before_submit' => true]);
        app(CandidateFormQuestionDefaults::class)->seedForOrganization($organization);

        session(['organization_id' => $organization->id]);

        $this->actingAs($user)->post(route('report-orders.store'), [
            'subject_name' => 'Taylor Candidate',
            'candidate_email' => 'taylor@example.test',
            'screening_package_id' => $package->id,
        ]);

        $reportOrder = ReportOrder::query()->firstOrFail();
        $this->completeCandidateIntake($reportOrder);

        $operationsUser = User::factory()->create(['email_verified_at' => now()]);
        $operationsUser->assignRole(PlatformRole::Operations);
        $reviewer = User::factory()->create(['email_verified_at' => now(), 'name' => 'Ops Reviewer']);
        $reviewer->assignRole(PlatformRole::Admin);

        $this->actingAs($operationsUser)
            ->get(route('platform.report-orders.index', ['organization_id' => $organization->id, 'q' => 'Taylor']))
            ->assertOk()
            ->assertSee('Taylor Candidate')
            ->assertSee($organization->name);

        $this->actingAs($operationsUser)
            ->patch(route('platform.report-orders.assign', $reportOrder), [
                'assigned_to_user_id' => $reviewer->id,
            ])
            ->assertRedirect();

        $reportOrder->refresh();

        $this->assertSame(ReportOrderStatus::Assigned, $reportOrder->status);
        $this->assertSame($reviewer->id, $reportOrder->assigned_to_user_id);
    }

    public function test_platform_user_can_approve_pending_request(): void
    {
        [$organization, $package, $user] = $this->clientContext();
        $package->searchTypes()->firstOrFail()->update(['requires_review_before_submit' => true]);
        app(CandidateFormQuestionDefaults::class)->seedForOrganization($organization);

        session(['organization_id' => $organization->id]);
        $this->actingAs($user)->post(route('report-orders.store'), [
            'subject_name' => 'Sam Candidate',
            'candidate_email' => 'sam@example.test',
            'screening_package_id' => $package->id,
        ]);

        $reportOrder = ReportOrder::query()->firstOrFail();
        $this->completeCandidateIntake($reportOrder);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(PlatformRole::Admin);

        $this->actingAs($admin)
            ->patch(route('platform.report-orders.approve', $reportOrder), [
                'review_notes' => 'Looks good',
            ])
            ->assertRedirect();

        $reportOrder->refresh();

        $this->assertSame(ReportOrderStatus::Submitted, $reportOrder->status);
        $this->assertNotNull($reportOrder->submitted_at);
        $this->assertSame('Looks good', $reportOrder->review_notes);
    }

    public function test_platform_cannot_approve_while_awaiting_candidate(): void
    {
        [$organization, $package, $user] = $this->clientContext();
        $package->searchTypes()->firstOrFail()->update(['requires_review_before_submit' => true]);

        session(['organization_id' => $organization->id]);
        $this->actingAs($user)->post(route('report-orders.store'), [
            'subject_name' => 'Sam Candidate',
            'candidate_email' => 'sam@example.test',
            'screening_package_id' => $package->id,
        ]);

        $reportOrder = ReportOrder::query()->firstOrFail();
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(PlatformRole::Admin);

        $this->actingAs($admin)
            ->patch(route('platform.report-orders.approve', $reportOrder), [
                'review_notes' => 'Too early',
            ])
            ->assertForbidden();
    }

    public function test_expired_invite_link_requires_client_resend(): void
    {
        [$organization, $package, $user] = $this->clientContext();
        app(CandidateFormQuestionDefaults::class)->seedForOrganization($organization);

        session(['organization_id' => $organization->id]);
        $this->actingAs($user)->post(route('report-orders.store'), [
            'subject_name' => 'Expired Candidate',
            'candidate_email' => 'expired@example.test',
            'screening_package_id' => $package->id,
        ]);

        $reportOrder = ReportOrder::query()->firstOrFail();
        $expiredToken = $reportOrder->invite_token;

        $reportOrder->forceFill([
            'invite_sent_at' => now()->subDays(4),
        ])->save();

        $this->get(route('candidate.intake.show', $expiredToken))
            ->assertOk()
            ->assertSee('This link has expired')
            ->assertSee('resend your invitation');

        $this->post(route('candidate.intake.store', $expiredToken), [
            'answers' => [
                'legal_name' => 'Expired Candidate',
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
        ])->assertStatus(410);

        session(['organization_id' => $organization->id]);
        $this->actingAs($user)
            ->post(route('report-orders.resend-invite', $reportOrder))
            ->assertRedirect();

        $reportOrder->refresh();
        $this->assertNotSame($expiredToken, $reportOrder->invite_token);
        $this->assertFalse($reportOrder->isInviteExpired());

        $this->completeCandidateIntake($reportOrder);
        $reportOrder->refresh();
        $this->assertNotNull($reportOrder->candidate_completed_at);
    }

    public function test_platform_user_can_save_and_delete_report_order_filter_sets(): void
    {
        [$organization, $package, $user] = $this->clientContext();

        session(['organization_id' => $organization->id]);
        $this->actingAs($user)->post(route('report-orders.store'), [
            'subject_name' => 'Taylor Candidate',
            'candidate_email' => 'taylor@example.test',
            'screening_package_id' => $package->id,
        ]);

        $operationsUser = User::factory()->create(['email_verified_at' => now()]);
        $operationsUser->assignRole(PlatformRole::Operations);

        $filterParams = [
            'organization_id' => $organization->id,
            'status' => ReportOrderStatus::AwaitingCandidate->value,
            'q' => 'Taylor',
        ];

        $this->actingAs($operationsUser)
            ->post(route('platform.report-orders.filters.store'), [
                'name' => 'Awaiting Taylor intake',
                ...$filterParams,
            ])
            ->assertRedirect(route('platform.report-orders.index', $filterParams))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('saved_report_order_filters', [
            'user_id' => $operationsUser->id,
            'name' => 'Awaiting Taylor intake',
        ]);

        $savedFilter = $operationsUser->savedReportOrderFilters()->firstOrFail();
        $this->assertSame(
            SavedReportOrderFilter::normalizeFilters($filterParams),
            $savedFilter->filters,
        );

        $this->actingAs($operationsUser)
            ->get(route('platform.report-orders.index', $savedFilter->filters))
            ->assertOk()
            ->assertSee('Taylor Candidate')
            ->assertSee('Awaiting Taylor intake');

        $this->actingAs($operationsUser)
            ->delete(route('platform.report-orders.filters.destroy', $savedFilter), $filterParams)
            ->assertRedirect(route('platform.report-orders.index', $filterParams))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('saved_report_order_filters', [
            'id' => $savedFilter->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_saved_filter(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $owner->assignRole(PlatformRole::Operations);

        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        $otherUser->assignRole(PlatformRole::Operations);

        $savedFilter = $owner->savedReportOrderFilters()->create([
            'name' => 'My queue',
            'filters' => ['status' => ReportOrderStatus::Assigned->value],
        ]);

        $this->actingAs($otherUser)
            ->delete(route('platform.report-orders.filters.destroy', $savedFilter))
            ->assertForbidden();

        $this->assertDatabaseHas('saved_report_order_filters', ['id' => $savedFilter->id]);
    }

    private function completeCandidateIntake(ReportOrder $reportOrder): void
    {
        $token = $reportOrder->invite_token;
        $this->assertNotNull($token);

        $this->post(route('candidate.intake.store', $token), [
            'answers' => [
                'legal_name' => $reportOrder->subject_name,
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
        $organization->screeningPackages()->attach($package->id, ['tenant_id' => $organization->tenant_id]);

        return [$organization, $package->fresh(['searchTypes']), $user];
    }
}
