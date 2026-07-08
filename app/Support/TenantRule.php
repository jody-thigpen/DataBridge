<?php

namespace App\Support;

use App\Services\TenantContext;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class TenantRule
{
    public static function unique(string $table, string $column = 'NULL'): Unique
    {
        $rule = Rule::unique($table, $column === 'NULL' ? null : $column);

        $tenantId = app(TenantContext::class)->id();

        if ($tenantId !== null) {
            $rule->where('tenant_id', $tenantId);
        }

        return $rule;
    }
}
