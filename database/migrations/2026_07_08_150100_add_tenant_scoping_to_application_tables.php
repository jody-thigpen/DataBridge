<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addTenantColumn('users');
        $this->addTenantColumn('organizations');
        $this->addTenantColumn('role_assignments');
        $this->addTenantColumn('data_sources');
        $this->addTenantColumn('search_types');
        $this->addTenantColumn('screening_packages');
        $this->addTenantColumn('report_orders');
        $this->addTenantColumn('organization_package_prices');
        $this->addTenantColumn('organization_search_type_settings');
        $this->addTenantColumn('saved_report_order_filters');
        $this->addTenantColumn('package_search_type');
        $this->addTenantColumn('organization_screening_package');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['tenant_id', 'email']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::table('data_sources', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::table('search_types', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropUnique(['code']);
            $table->unique(['tenant_id', 'slug']);
            $table->unique(['tenant_id', 'code']);
        });

        Schema::table('screening_packages', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('screening_packages', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'slug']);
            $table->unique(['slug']);
        });

        Schema::table('search_types', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'slug']);
            $table->dropUnique(['tenant_id', 'code']);
            $table->unique(['slug']);
            $table->unique(['code']);
        });

        Schema::table('data_sources', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'slug']);
            $table->unique(['slug']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'slug']);
            $table->unique(['slug']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'email']);
            $table->unique(['email']);
        });

        foreach ([
            'organization_screening_package',
            'package_search_type',
            'saved_report_order_filters',
            'organization_search_type_settings',
            'organization_package_prices',
            'report_orders',
            'screening_packages',
            'search_types',
            'data_sources',
            'role_assignments',
            'organizations',
            'users',
        ] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }
    }

    private function addTenantColumn(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) {
            $table->foreignId('tenant_id')->default(1)->after('id')->constrained()->cascadeOnDelete();
        });

        DB::table($tableName)->whereNull('tenant_id')->update(['tenant_id' => 1]);
    }
};
