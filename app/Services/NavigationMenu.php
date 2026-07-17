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
            $platformItems = [
                $this->item('Clients', 'platform.clients.index', ['platform.clients.*']),
                $this->item('Data sources', 'platform.data-sources.index', ['platform.data-sources.*']),
                $this->item('Search types', 'platform.search-types.index', ['platform.search-types.*']),
                $this->item('Packages', 'platform.packages.index', ['platform.packages.*']),
            ];

            if ($user->hasPermission(Permission::PlatformReportOrdersView)) {
                $platformItems[] = $this->item('Report orders', 'platform.report-orders.index', ['platform.report-orders.*']);
            }

            $platformItems[] = $this->item('Platform users', 'platform.users.index', ['platform.users.*']);

            $sections[] = [
                'label' => 'Platform administration',
                'items' => $platformItems,
            ];
        }

        $organization = $this->organizationContext->current($user);

        if ($organization instanceof Organization) {
            $workspaceItems = [];

            if ($user->hasPermission(Permission::OrgOrdersCreate, $organization)) {
                $workspaceItems[] = $this->item('New report order', 'report-orders.create', ['report-orders.create']);
            }

            if ($user->hasPermission(Permission::OrgOrdersView, $organization)
                || $user->hasPermission(Permission::OrgOrdersViewAll, $organization)) {
                $workspaceItems[] = $this->item('Report orders', 'report-orders.index', ['report-orders.index', 'report-orders.resend-invite']);
            }

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
