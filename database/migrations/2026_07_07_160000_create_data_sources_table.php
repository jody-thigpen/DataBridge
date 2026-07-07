<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('driver');
            $table->string('base_url');
            $table->string('documentation_url')->nullable();
            $table->text('description')->nullable();
            $table->json('capabilities')->nullable();
            $table->text('config')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_connected_at')->nullable();
            $table->string('last_connection_status')->nullable();
            $table->text('last_connection_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_sources');
    }
};
