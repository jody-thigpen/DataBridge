<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\PlatformRole;
use App\Models\Concerns\HasRoleAssignments;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'is_active',
    'suspended_at',
    'current_organization_id',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoleAssignments, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'suspended_at' => 'datetime',
        ];
    }

    public function scopeClientManagers(Builder $query): Builder
    {
        return $query
            ->whereHas('roleAssignments', function (Builder $assignment): void {
                $assignment
                    ->whereNull('organization_id')
                    ->whereHas('role', fn (Builder $role) => $role->where('slug', PlatformRole::ClientManager->value));
            })
            ->orderBy('name');
    }

    public function managedOrganizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'client_manager_id');
    }

    public function savedReportRequestFilters(): HasMany
    {
        return $this->hasMany(SavedReportRequestFilter::class);
    }
}
