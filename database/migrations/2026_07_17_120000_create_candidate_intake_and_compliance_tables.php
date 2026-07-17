<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_form_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('help_text')->nullable();
            $table->string('field_key');
            $table->string('field_type');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'field_key']);
            $table->index(['organization_id', 'is_active', 'sort_order']);
        });

        Schema::create('compliance_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('document_type');
            $table->text('description')->nullable();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('require_acknowledgment')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'is_active', 'sort_order']);
        });

        Schema::table('report_requests', function (Blueprint $table) {
            $table->string('candidate_email')->nullable()->after('subject_name');
            $table->string('candidate_phone')->nullable()->after('candidate_email');
            $table->string('invite_token', 64)->nullable()->unique()->after('candidate_phone');
            $table->timestamp('invite_sent_at')->nullable()->after('invite_token');
            $table->timestamp('candidate_opened_at')->nullable()->after('invite_sent_at');
            $table->timestamp('candidate_completed_at')->nullable()->after('candidate_opened_at');
            $table->timestamp('authorization_accepted_at')->nullable()->after('candidate_completed_at');
            $table->string('authorization_ip', 45)->nullable()->after('authorization_accepted_at');
            $table->text('authorization_user_agent')->nullable()->after('authorization_ip');
            $table->json('candidate_answers')->nullable()->after('authorization_user_agent');
            $table->json('acknowledged_document_ids')->nullable()->after('candidate_answers');
        });
    }

    public function down(): void
    {
        Schema::table('report_requests', function (Blueprint $table) {
            $table->dropColumn([
                'candidate_email',
                'candidate_phone',
                'invite_token',
                'invite_sent_at',
                'candidate_opened_at',
                'candidate_completed_at',
                'authorization_accepted_at',
                'authorization_ip',
                'authorization_user_agent',
                'candidate_answers',
                'acknowledged_document_ids',
            ]);
        });

        Schema::dropIfExists('compliance_documents');
        Schema::dropIfExists('candidate_form_questions');
    }
};
