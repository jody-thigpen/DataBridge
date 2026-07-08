<?php

namespace App\Services;

use App\Enums\Permission;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationService
{
    public const IMPERSONATOR_KEY = 'impersonator_id';

    public const STARTED_AT_KEY = 'impersonation_started_at';

    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    public function isImpersonating(): bool
    {
        return Session::has(self::IMPERSONATOR_KEY);
    }

    public function impersonator(): ?User
    {
        $id = Session::get(self::IMPERSONATOR_KEY);

        return $id ? User::query()->find($id) : null;
    }

    public function canImpersonate(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission(Permission::PlatformUsersManage);
    }

    public function start(User $impersonator, User $target): void
    {
        if (! $this->canImpersonate($impersonator)) {
            abort(403);
        }

        if ($impersonator->is($target)) {
            abort(422, 'You cannot masquerade as yourself.');
        }

        if ((int) $impersonator->tenant_id !== (int) $target->tenant_id) {
            abort(403, 'You cannot masquerade as a user in another tenant.');
        }

        if ($target->isPlatformUser() && ! $impersonator->isSuperAdmin()) {
            abort(403, 'Only a super admin can masquerade as platform staff.');
        }

        Session::put(self::IMPERSONATOR_KEY, $impersonator->id);
        Session::put(self::STARTED_AT_KEY, now()->toIso8601String());

        Auth::login($target);

        $organization = $target->accessibleOrganizations()->first();

        if ($organization instanceof Organization) {
            $this->organizationContext->set($organization, $target);
        } else {
            $this->organizationContext->clear();
        }
    }

    public function stop(): void
    {
        $impersonator = $this->impersonator();

        if ($impersonator === null) {
            return;
        }

        Session::forget([self::IMPERSONATOR_KEY, self::STARTED_AT_KEY]);
        $this->organizationContext->clear();

        Auth::login($impersonator);
    }
}
