<?php

namespace App\Services;

use App\Enums\Permission;
use App\Models\Organization;
use App\Models\User;

class NavigationMenu
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly ImpersonationService $impersonation,
    ) {}

    /**
     * @return list<array{label: string, items: list<array{label: string, route: string, active: bool}>}>
     */
    public function sections(?User $user = null): array
    {
        $user ??= auth()->user();

        if ($user === null) {
            return [];
        }

        $sections = [
            [
                'label' => 'Overview',
                'items' => [
                    $this->item('Dashboard', 'dashboard', ['dashboard']),
                ],
            ],
        ];

        if ($user->isPlatformUser() && ! $this->impersonation->isImpersonating()) {
            $sections[] = [
                'label' => 'Platform administration',
                'items' => [
                    $this->item('Clients', 'platform.clients.index', ['platform.clients.*']),
                    $this->item('Platform users', 'platform.users.index', ['platform.users.*']),
                ],
            ];
        }

        $organization = $this->organizationContext->current($user);

        if ($organization instanceof Organization) {
            $workspaceItems = [
                $this->item('New report request', 'reports.requests.create', ['reports.requests.*']),
                $this->item('Reports', 'reports.index', ['reports.index']),
            ];

            if ($user->hasPermission(Permission::OrgUsersManage, $organization)
                || $user->hasPermission(Permission::OrgUsersInvite, $organization)) {
                $workspaceItems[] = $this->item('Organization users', 'organization.users.index', ['organization.users.*']);
            }

            $workspaceItems[] = $this->item('Organization profile', 'organization.profile.edit', ['organization.profile.*']);

            if ($user->hasPermission(Permission::OrgBillingManage, $organization)) {
                $workspaceItems[] = $this->item('Billing & payments', 'organization.billing.edit', ['organization.billing.*']);
            }

            $sections[] = [
                'label' => $organization->name,
                'items' => $workspaceItems,
            ];
        }

        $sections[] = [
            'label' => 'Account',
            'items' => [
                $this->item('My profile', 'profile.edit', ['profile.*']),
            ],
        ];

        return $sections;
    }

    /**
     * @return list<array{label: string, route: string, active: bool}>
     */
    public function items(?User $user = null): array
    {
        return collect($this->sections($user))
            ->flatMap(fn (array $section) => $section['items'])
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $patterns
     * @return array{label: string, route: string, active: bool}
     */
    private function item(string $label, string $route, array $patterns): array
    {
        $active = collect($patterns)->contains(fn (string $pattern) => request()->routeIs($pattern));

        return [
            'label' => $label,
            'route' => $route,
            'active' => $active,
        ];
    }
}
