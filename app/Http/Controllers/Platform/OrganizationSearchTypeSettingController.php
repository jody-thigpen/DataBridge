<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationSearchTypeSetting;
use App\Models\SearchType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationSearchTypeSettingController extends Controller
{
    public function update(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        $validated = $request->validate([
            'settings' => ['present', 'array'],
            'settings.*.search_type_id' => ['required', 'exists:search_types,id'],
            'settings.*.requires_review_before_submit' => ['nullable', 'in:default,1,0'],
        ]);

        foreach ($validated['settings'] as $row) {
            $searchTypeId = (int) $row['search_type_id'];
            $value = $row['requires_review_before_submit'] ?? 'default';

            if ($value === 'default') {
                OrganizationSearchTypeSetting::query()
                    ->where('organization_id', $organization->id)
                    ->where('search_type_id', $searchTypeId)
                    ->delete();

                continue;
            }

            OrganizationSearchTypeSetting::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'search_type_id' => $searchTypeId,
                ],
                [
                    'requires_review_before_submit' => $value === '1',
                ],
            );
        }

        return back()->with('status', "Search review settings updated for {$organization->name}.");
    }

    private function canManageCatalog(?User $user): bool
    {
        return $user !== null && $user->hasPermission(Permission::PlatformCatalogManage);
    }
}
