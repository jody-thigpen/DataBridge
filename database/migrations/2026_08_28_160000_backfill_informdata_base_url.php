<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $baseUrl = rtrim(config('informdata.base_url'), '/');

        DB::table('data_sources')
            ->where('driver', 'informdata')
            ->where(function ($query): void {
                $query->whereNull('base_url')->orWhere('base_url', '');
            })
            ->update(['base_url' => $baseUrl]);
    }

    public function down(): void
    {
        // No-op: base URLs may have been intentionally configured.
    }
};
