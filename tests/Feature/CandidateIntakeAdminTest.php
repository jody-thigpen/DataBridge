<?php

namespace Tests\Feature;

use App\Enums\CandidateFormQuestionType;
use App\Enums\ComplianceDocumentType;
use App\Enums\PlatformRole;
use App\Models\ComplianceDocument;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidateIntakeAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_platform_admin_can_configure_candidate_questions_for_client(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(PlatformRole::Admin);
        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);

        $this->actingAs($admin)
            ->post(route('platform.clients.candidate-questions.defaults', $organization))
            ->assertRedirect();

        $this->assertGreaterThan(0, $organization->candidateFormQuestions()->count());

        $this->actingAs($admin)
            ->post(route('platform.clients.candidate-questions.store', $organization), [
                'label' => 'Driver license state',
                'field_type' => CandidateFormQuestionType::Text->value,
                'help_text' => 'Two-letter state code',
                'is_required' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('candidate_form_questions', [
            'organization_id' => $organization->id,
            'label' => 'Driver license state',
            'field_key' => 'driver_license_state',
        ]);
    }

    public function test_platform_admin_can_upload_compliance_document(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(PlatformRole::Admin);
        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);

        $file = UploadedFile::fake()->create('authorization.pdf', 120, 'application/pdf');

        $this->actingAs($admin)
            ->post(route('platform.clients.compliance-documents.store', $organization), [
                'name' => 'Background Check Authorization',
                'document_type' => ComplianceDocumentType::Authorization->value,
                'description' => 'Candidate authorization form',
                'require_acknowledgment' => '1',
                'document' => $file,
            ])
            ->assertRedirect();

        $document = ComplianceDocument::query()->firstOrFail();

        $this->assertSame('Background Check Authorization', $document->name);
        $this->assertTrue($document->require_acknowledgment);
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_support_user_cannot_manage_candidate_questions(): void
    {
        $support = User::factory()->create(['email_verified_at' => now()]);
        $support->assignRole(PlatformRole::Support);
        $organization = Organization::query()->create(['name' => 'Client Co', 'slug' => 'client-co']);

        $this->actingAs($support)
            ->post(route('platform.clients.candidate-questions.store', $organization), [
                'label' => 'Should fail',
                'field_type' => CandidateFormQuestionType::Text->value,
            ])
            ->assertForbidden();
    }
}
