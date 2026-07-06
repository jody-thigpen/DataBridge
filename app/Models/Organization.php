<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
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
