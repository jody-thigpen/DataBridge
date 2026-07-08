<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationPackagePrice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationPackagePriceController extends Controller
{
    public function update(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        $validated = $request->validate([
            'packages' => ['present', 'array'],
            'packages.*.screening_package_id' => ['required', 'exists:screening_packages,id'],
            'packages.*.assigned' => ['sometimes', 'boolean'],
            'packages.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $assignedPackageIds = [];

        foreach ($validated['packages'] as $row) {
            $packageId = (int) $row['screening_package_id'];
            $isAssigned = filter_var($row['assigned'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (! $isAssigned) {
                continue;
            }

            $assignedPackageIds[] = $packageId;
            $price = $row['price'] ?? null;

            if ($price === null || $price === '') {
                OrganizationPackagePrice::query()
                    ->where('organization_id', $organization->id)
                    ->where('screening_package_id', $packageId)
                    ->delete();

                continue;
            }

            OrganizationPackagePrice::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'screening_package_id' => $packageId,
                ],
                [
                    'price' => $price,
                ],
            );
        }

        $tenantId = $organization->tenant_id
            ?? app(\App\Services\TenantContext::class)->id()
            ?? \App\Models\Tenant::query()->where('slug', config('tenancy.default_slug'))->value('id');
        $sync = collect($assignedPackageIds)
            ->mapWithKeys(fn (int $packageId) => [$packageId => ['tenant_id' => $tenantId]])
            ->all();

        $organization->screeningPackages()->sync($sync);

        OrganizationPackagePrice::query()
            ->where('organization_id', $organization->id)
            ->whereNotIn('screening_package_id', $assignedPackageIds)
            ->delete();

        return back()->with('status', "Package assignments and pricing updated for {$organization->name}.");
    }

    private function canManageCatalog(?User $user): bool
    {
        return $user !== null && $user->hasPermission(Permission::PlatformCatalogManage);
    }
}
