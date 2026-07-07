<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_source_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('screening_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('package_search_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screening_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('search_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('data_source_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['screening_package_id', 'search_type_id']);
        });

        Schema::create('organization_package_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('screening_package_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(['organization_id', 'screening_package_id'], 'org_package_price_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_package_prices');
        Schema::dropIfExists('package_search_type');
        Schema::dropIfExists('screening_packages');
        Schema::dropIfExists('search_types');
    }
};
