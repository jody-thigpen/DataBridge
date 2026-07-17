<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('report_requests') && ! Schema::hasTable('report_orders')) {
            Schema::rename('report_requests', 'report_orders');
        }

        if (Schema::hasTable('report_orders') && Schema::hasColumn('report_orders', 'requested_by_user_id')) {
            foreach (Schema::getForeignKeys('report_orders') as $foreignKey) {
                if (in_array('requested_by_user_id', $foreignKey['columns'], true)) {
                    Schema::table('report_orders', function (Blueprint $table) use ($foreignKey) {
                        $table->dropForeign($foreignKey['name']);
                    });
                }
            }

            Schema::table('report_orders', function (Blueprint $table) {
                $table->renameColumn('requested_by_user_id', 'ordered_by_user_id');
            });

            Schema::table('report_orders', function (Blueprint $table) {
                $table->foreign('ordered_by_user_id')->references('id')->on('users');
            });
        }

        if (Schema::hasTable('saved_report_request_filters') && ! Schema::hasTable('saved_report_order_filters')) {
            Schema::rename('saved_report_request_filters', 'saved_report_order_filters');
        }

        $permissionRenames = [
            'platform.report_requests.view' => [
                'slug' => 'platform.report_orders.view',
                'name' => 'View report order queue across clients',
                'description' => 'View report order queue across clients',
            ],
            'platform.report_requests.manage' => [
                'slug' => 'platform.report_orders.manage',
                'name' => 'Assign and review report orders',
                'description' => 'Assign and review report orders',
            ],
        ];

        foreach ($permissionRenames as $oldSlug => $attributes) {
            DB::table('permissions')
                ->where('slug', $oldSlug)
                ->update($attributes);
        }
    }

    public function down(): void
    {
        $permissionRenames = [
            'platform.report_orders.view' => [
                'slug' => 'platform.report_requests.view',
                'name' => 'View report request queue across clients',
                'description' => 'View report request queue across clients',
            ],
            'platform.report_orders.manage' => [
                'slug' => 'platform.report_requests.manage',
                'name' => 'Assign and review report requests',
                'description' => 'Assign and review report requests',
            ],
        ];

        foreach ($permissionRenames as $oldSlug => $attributes) {
            DB::table('permissions')
                ->where('slug', $oldSlug)
                ->update($attributes);
        }

        if (Schema::hasTable('saved_report_order_filters') && ! Schema::hasTable('saved_report_request_filters')) {
            Schema::rename('saved_report_order_filters', 'saved_report_request_filters');
        }

        if (Schema::hasTable('report_orders') && Schema::hasColumn('report_orders', 'ordered_by_user_id')) {
            foreach (Schema::getForeignKeys('report_orders') as $foreignKey) {
                if (in_array('ordered_by_user_id', $foreignKey['columns'], true)) {
                    Schema::table('report_orders', function (Blueprint $table) use ($foreignKey) {
                        $table->dropForeign($foreignKey['name']);
                    });
                }
            }

            Schema::table('report_orders', function (Blueprint $table) {
                $table->renameColumn('ordered_by_user_id', 'requested_by_user_id');
            });

            Schema::table('report_orders', function (Blueprint $table) {
                $table->foreign('requested_by_user_id')->references('id')->on('users');
            });
        }

        if (Schema::hasTable('report_orders') && ! Schema::hasTable('report_requests')) {
            Schema::rename('report_orders', 'report_requests');
        }
    }
};
