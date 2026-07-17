<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_types', function (Blueprint $table) {
            $table->boolean('requires_review_before_submit')
                ->default(true)
                ->after('is_active');
        });

        Schema::create('organization_search_type_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('search_type_id')->constrained()->cascadeOnDelete();
            $table->boolean('requires_review_before_submit')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'search_type_id'], 'org_search_type_settings_unique');
        });

        Schema::create('report_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('screening_package_id')->constrained();
            $table->foreignId('ordered_by_user_id')->constrained('users');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_name');
            $table->text('notes')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('status', 32);
            $table->boolean('requires_review');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['status']);
            $table->index(['organization_id', 'created_at']);
            $table->index(['assigned_to_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_orders');
        Schema::dropIfExists('organization_search_type_settings');

        Schema::table('search_types', function (Blueprint $table) {
            $table->dropColumn('requires_review_before_submit');
        });
    }
};
