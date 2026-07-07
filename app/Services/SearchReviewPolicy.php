<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationSearchTypeSetting;
use App\Models\ScreeningPackage;
use App\Models\SearchType;
use Illuminate\Support\Collection;

class SearchReviewPolicy
{
    public function requiresReviewForOrganization(SearchType $searchType, Organization $organization): bool
    {
        $override = OrganizationSearchTypeSetting::query()
            ->where('organization_id', $organization->id)
            ->where('search_type_id', $searchType->id)
            ->value('requires_review_before_submit');

        if ($override !== null) {
            return (bool) $override;
        }

        return $searchType->requires_review_before_submit;
    }

    public function packageRequiresReview(ScreeningPackage $package, Organization $organization): bool
    {
        $package->loadMissing('searchTypes');

        foreach ($package->searchTypes as $searchType) {
            if ($this->requiresReviewForOrganization($searchType, $organization)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, array{search_type: SearchType, requires_review: bool, source: string}>
     */
    public function reviewBreakdown(ScreeningPackage $package, Organization $organization): Collection
    {
        $package->loadMissing('searchTypes');

        $overrides = OrganizationSearchTypeSetting::query()
            ->where('organization_id', $organization->id)
            ->whereIn('search_type_id', $package->searchTypes->pluck('id'))
            ->get()
            ->keyBy('search_type_id');

        return $package->searchTypes->map(function (SearchType $searchType) use ($overrides): array {
            $override = $overrides->get($searchType->id);
            $requiresReview = $override?->requires_review_before_submit ?? $searchType->requires_review_before_submit;

            return [
                'search_type' => $searchType,
                'requires_review' => (bool) $requiresReview,
                'source' => $override !== null ? 'client override' : 'search default',
            ];
        });
    }
}
