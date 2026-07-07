<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationPackagePrice extends Model
{
    protected $fillable = [
        'organization_id',
        'screening_package_id',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function screeningPackage(): BelongsTo
    {
        return $this->belongsTo(ScreeningPackage::class);
    }
}
