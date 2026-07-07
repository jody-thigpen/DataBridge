<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ScreeningPackage extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'base_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ScreeningPackage $package): void {
            if (blank($package->slug)) {
                $package->slug = Str::slug($package->name);
            }
        });
    }

    public function searchTypes(): BelongsToMany
    {
        return $this->belongsToMany(SearchType::class, 'package_search_type')
            ->withPivot(['data_source_id', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function organizationPrices(): HasMany
    {
        return $this->hasMany(OrganizationPackagePrice::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_screening_package')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function scopeAssignedTo(Builder $query, Organization $organization): Builder
    {
        return $query->whereHas('organizations', fn (Builder $relation) => $relation->where('organizations.id', $organization->id));
    }

    public function isAssignedTo(?Organization $organization): bool
    {
        if ($organization === null) {
            return false;
        }

        return $this->organizations()
            ->where('organizations.id', $organization->id)
            ->exists();
    }

    public function priceForOrganization(?Organization $organization): string
    {
        if ($organization !== null) {
            $override = $this->organizationPrices()
                ->where('organization_id', $organization->id)
                ->value('price');

            if ($override !== null) {
                return number_format((float) $override, 2, '.', '');
            }
        }

        return number_format((float) $this->base_price, 2, '.', '');
    }

    public function formattedBasePrice(): string
    {
        return '$'.number_format((float) $this->base_price, 2);
    }

    public function formattedPriceForOrganization(?Organization $organization): string
    {
        return '$'.number_format((float) $this->priceForOrganization($organization), 2);
    }

    /**
     * @param  list<array{search_type_id: int|string, data_source_id: int|string}>  $items
     */
    public function syncSearchItems(array $items): void
    {
        $sync = [];

        foreach ($items as $index => $item) {
            $sync[(int) $item['search_type_id']] = [
                'data_source_id' => (int) $item['data_source_id'],
                'sort_order' => ($index + 1) * 10,
            ];
        }

        $this->searchTypes()->sync($sync);
    }
}
