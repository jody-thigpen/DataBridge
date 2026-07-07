<?php

namespace App\Models;

use App\Enums\SearchTypeCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class SearchType extends Model
{
    protected $fillable = [
        'data_source_id',
        'name',
        'slug',
        'code',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SearchType $searchType): void {
            if (blank($searchType->slug)) {
                $searchType->slug = Str::slug($searchType->name);
            }
        });
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function screeningPackages(): BelongsToMany
    {
        return $this->belongsToMany(ScreeningPackage::class, 'package_search_type')
            ->withPivot(['data_source_id', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function codeEnum(): SearchTypeCode
    {
        return SearchTypeCode::from($this->code);
    }
}
