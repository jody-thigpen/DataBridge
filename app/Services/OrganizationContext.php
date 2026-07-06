<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class OrganizationContext
{
    public const SESSION_KEY = 'organization_id';

    public function current(?User $user = null): ?Organization
    {
        $user ??= auth()->user();

        if ($user === null) {
            return null;
        }

        $organizationId = Session::get(self::SESSION_KEY) ?? $user->current_organization_id;

        if ($organizationId === null) {
            $organizationId = $user->accessibleOrganizations()->first()?->id;
        }

        if ($organizationId === null) {
            return null;
        }

        $organization = Organization::query()->find($organizationId);

        if ($organization === null || ! $user->canAccessOrganization($organization)) {
            return null;
        }

        return $organization;
    }

    public function set(Organization $organization, ?User $user = null): void
    {
        $user ??= auth()->user();

        if ($user === null || ! $user->canAccessOrganization($organization)) {
            abort(403);
        }

        Session::put(self::SESSION_KEY, $organization->id);

        if ($user->current_organization_id !== $organization->id) {
            $user->forceFill(['current_organization_id' => $organization->id])->save();
        }
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function exitPlatformView(?User $user = null): void
    {
        $user ??= auth()->user();

        if ($user === null || ! $user->isPlatformUser()) {
            abort(403);
        }

        $this->clear();

        if ($user->current_organization_id !== null) {
            $user->forceFill(['current_organization_id' => null])->save();
        }
    }
}
