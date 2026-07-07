<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationPackagePrice;
use App\Models\ScreeningPackage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationPackagePriceController extends Controller
{
    public function update(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        $validated = $request->validate([
            'prices' => ['required', 'array'],
            'prices.*.screening_package_id' => ['required', 'exists:screening_packages,id'],
            'prices.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($validated['prices'] as $row) {
            $packageId = (int) $row['screening_package_id'];
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

        return back()->with('status', "Package pricing updated for {$organization->name}.");
    }

    private function canManageCatalog(?User $user): bool
    {
        return $user !== null && $user->hasPermission(Permission::PlatformCatalogManage);
    }
}
