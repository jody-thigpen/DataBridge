<?php

namespace App\Models;

use App\Enums\RoleScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'scope',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'scope' => RoleScope::class,
            'sort_order' => 'integer',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    public function isPlatform(): bool
    {
        return $this->scope === RoleScope::Platform;
    }

    public function isOrganization(): bool
    {
        return $this->scope === RoleScope::Organization;
    }
}
