<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSearchTypeSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'search_type_id',
        'requires_review_before_submit',
    ];

    protected function casts(): array
    {
        return [
            'requires_review_before_submit' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function searchType(): BelongsTo
    {
        return $this->belongsTo(SearchType::class);
    }
}
