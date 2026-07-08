<?php

namespace App\Models\Concerns;

use App\Enums\OrganizationRole;
use App\Enums\Permission as PermissionEnum;
use App\Enums\PlatformRole;
use App\Models\Organization;
use App\Models\Role;
use App\Models\RoleAssignment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

trait HasRoleAssignments
{
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'role_assignments')
            ->withPivot(['role_id', 'id'])
            ->withTimestamps()
            ->whereNotNull('role_assignments.organization_id');
    }

    public function currentOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'current_organization_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasPlatformRole(PlatformRole::SuperAdmin);
    }

    public function isPlatformUser(): bool
    {
        return $this->platformAssignments()->exists();
    }

    public function canAccessOrganization(Organization $organization): bool
    {
        if ($this->isPlatformUser()) {
            return true;
        }

        return $this->belongsToOrganization($organization);
    }

    public function hasPlatformRole(PlatformRole|string $role): bool
    {
        $slug = $role instanceof PlatformRole ? $role->value : $role;

        return $this->platformAssignments()
            ->whereHas('role', fn ($query) => $query->where('slug', $slug))
            ->exists();
    }

    public function hasOrganizationRole(OrganizationRole|string $role, Organization|int $organization): bool
    {
        $slug = $role instanceof OrganizationRole ? $role->value : $role;
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        return $this->organizationAssignments($organizationId)
            ->whereHas('role', fn ($query) => $query->where('slug', $slug))
            ->exists();
    }

    public function belongsToOrganization(Organization|int $organization): bool
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        return $this->organizationAssignments($organizationId)->exists();
    }

    public function hasPermission(PermissionEnum|string $permission, ?Organization $organization = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $slug = $permission instanceof PermissionEnum ? $permission->value : $permission;

        if (str_starts_with($slug, 'platform.')) {
            return $this->platformAssignments()
                ->whereHas('role.permissions', fn ($query) => $query->where('slug', $slug))
                ->exists();
        }

        if ($organization === null) {
            return false;
        }

        return $this->organizationAssignments($organization->id)
            ->whereHas('role.permissions', fn ($query) => $query->where('slug', $slug))
            ->exists();
    }

    public function assignRole(Role|PlatformRole|OrganizationRole|string $role, ?Organization $organization = null): RoleAssignment
    {
        $roleModel = $role instanceof Role
            ? $role
            : Role::query()->where('slug', is_string($role) ? $role : $role->value)->firstOrFail();

        if ($roleModel->isPlatform() && $organization !== null) {
            throw new \InvalidArgumentException('Platform roles cannot be assigned to an organization.');
        }

        if ($roleModel->isOrganization() && $organization === null) {
            throw new \InvalidArgumentException('Organization roles require an organization.');
        }

        return $this->roleAssignments()->firstOrCreate([
            'role_id' => $roleModel->id,
            'organization_id' => $organization?->id,
        ]);
    }

    public function removeRole(Role|PlatformRole|OrganizationRole|string $role, ?Organization $organization = null): void
    {
        $roleModel = $role instanceof Role
            ? $role
            : Role::query()->where('slug', is_string($role) ? $role : $role->value)->firstOrFail();

        $this->roleAssignments()
            ->where('role_id', $roleModel->id)
            ->when(
                $organization,
                fn ($query) => $query->where('organization_id', $organization->id),
                fn ($query) => $query->whereNull('organization_id'),
            )
            ->delete();
    }

    public function syncPlatformRole(Role $role): void
    {
        if (! $role->isPlatform()) {
            throw new \InvalidArgumentException('Role must be a platform role.');
        }

        $this->roleAssignments()->whereNull('organization_id')->delete();
        $this->assignRole($role);
    }

    public function syncOrganizationRole(Role $role, Organization $organization): void
    {
        if (! $role->isOrganization()) {
            throw new \InvalidArgumentException('Role must be an organization role.');
        }

        $this->roleAssignments()->where('organization_id', $organization->id)->delete();
        $this->assignRole($role, $organization);
    }

    public function accessibleOrganizations(): Collection
    {
        return $this->organizations()
            ->where('organizations.is_active', true)
            ->orderBy('organizations.name')
            ->get();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function isActiveAccount(): bool
    {
        return $this->is_active && ! $this->isSuspended();
    }

    protected function platformAssignments(): HasMany
    {
        return $this->roleAssignments()->whereNull('organization_id');
    }

    protected function organizationAssignments(int $organizationId): HasMany
    {
        return $this->roleAssignments()->where('organization_id', $organizationId);
    }
}
