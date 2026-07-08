<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'name',
        'slug',
        'is_active',
        'client_manager_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Organization $organization): void {
            if (blank($organization->slug)) {
                $organization->slug = Str::slug($organization->name);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function clientManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_manager_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_assignments')
            ->withPivot(['role_id', 'id'])
            ->withTimestamps();
    }

    public function packagePrices(): HasMany
    {
        return $this->hasMany(OrganizationPackagePrice::class);
    }

    public function screeningPackages(): BelongsToMany
    {
        return $this->belongsToMany(ScreeningPackage::class, 'organization_screening_package')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function assignedActivePackages(): BelongsToMany
    {
        return $this->screeningPackages()->where('screening_packages.is_active', true);
    }

    public function reportRequests(): HasMany
    {
        return $this->hasMany(ReportRequest::class);
    }

    public function searchTypeSettings(): HasMany
    {
        return $this->hasMany(OrganizationSearchTypeSetting::class);
    }

    public function isAncestorOf(self $organization): bool
    {
        $parent = $organization->parent;

        while ($parent !== null) {
            if ($parent->is($this)) {
                return true;
            }

            $parent = $parent->parent;
        }

        return false;
    }
}
