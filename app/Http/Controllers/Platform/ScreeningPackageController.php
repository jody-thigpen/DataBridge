<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\DataSource;
use App\Models\ScreeningPackage;
use App\Models\SearchType;
use App\Models\User;
use App\Support\TenantRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScreeningPackageController extends Controller
{
    public function index(Request $request): View
    {
        $packages = ScreeningPackage::query()
            ->withCount(['searchTypes', 'organizations'])
            ->orderBy('name')
            ->paginate(20);

        return view('platform.packages.index', [
            'packages' => $packages,
            'canManageCatalog' => $this->canManageCatalog($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        return view('platform.packages.create', [
            'searchTypes' => SearchType::query()->with('dataSource')->where('is_active', true)->orderBy('sort_order')->get(),
            'formItems' => $this->defaultFormItems(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        $validated = $this->validatePackage($request);

        $package = ScreeningPackage::query()->create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? null,
            'description' => $validated['description'] ?? null,
            'base_price' => $validated['base_price'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $package->syncSearchItems($validated['items']);

        return redirect()
            ->route('platform.packages.show', $package)
            ->with('status', "{$package->name} created.");
    }

    public function show(ScreeningPackage $screeningPackage): View
    {
        $screeningPackage->load(['searchTypes', 'organizations']);

        $dataSourcesById = \App\Models\DataSource::query()
            ->whereIn('id', $screeningPackage->searchTypes->pluck('pivot.data_source_id'))
            ->pluck('name', 'id');

        $clientPricesByOrganizationId = $screeningPackage->organizationPrices()
            ->whereIn('organization_id', $screeningPackage->organizations->pluck('id'))
            ->pluck('price', 'organization_id');

        return view('platform.packages.show', [
            'package' => $screeningPackage,
            'dataSourcesById' => $dataSourcesById,
            'clientPricesByOrganizationId' => $clientPricesByOrganizationId,
            'canManageCatalog' => $this->canManageCatalog(request()->user()),
        ]);
    }

    public function edit(Request $request, ScreeningPackage $screeningPackage): View
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        $screeningPackage->load(['searchTypes.dataSource']);

        $dataSources = DataSource::query()->orderBy('name')->get();
        $dataSourcesById = $dataSources->pluck('name', 'id');
        $packageSearchTypeIds = $screeningPackage->searchTypes->pluck('id');

        $availableSearchTypes = SearchType::query()
            ->with('dataSource')
            ->where('is_active', true)
            ->when(
                $packageSearchTypeIds->isNotEmpty(),
                fn ($query) => $query->whereNotIn('id', $packageSearchTypeIds),
            )
            ->orderBy('sort_order')
            ->get();

        return view('platform.packages.edit', [
            'package' => $screeningPackage,
            'dataSources' => $dataSources,
            'dataSourcesById' => $dataSourcesById,
            'availableSearchTypes' => $availableSearchTypes,
        ]);
    }

    public function update(Request $request, ScreeningPackage $screeningPackage): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        $validated = $this->validatePackageDetails($request, $screeningPackage);

        $screeningPackage->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'base_price' => $validated['base_price'],
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return redirect()
            ->route('platform.packages.edit', $screeningPackage)
            ->with('status', "{$screeningPackage->name} updated.");
    }

    public function storeSearchItem(Request $request, ScreeningPackage $screeningPackage): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        $attachedSearchTypeIds = $screeningPackage->searchTypes()->pluck('search_types.id');

        $validated = $request->validate([
            'search_type_id' => [
                'required',
                'exists:search_types,id',
                Rule::notIn($attachedSearchTypeIds),
            ],
            'data_source_id' => ['required', 'exists:data_sources,id'],
        ]);

        $sortOrder = ($screeningPackage->searchTypes()->count() + 1) * 10;

        $screeningPackage->searchTypes()->attach($validated['search_type_id'], [
            'data_source_id' => $validated['data_source_id'],
            'sort_order' => $sortOrder,
            'tenant_id' => $screeningPackage->tenant_id
                ?? app(\App\Services\TenantContext::class)->id()
                ?? \App\Models\Tenant::query()->where('slug', config('tenancy.default_slug'))->value('id'),
        ]);

        $searchType = SearchType::query()->findOrFail($validated['search_type_id']);

        return redirect()
            ->route('platform.packages.edit', $screeningPackage)
            ->with('status', "{$searchType->name} added to {$screeningPackage->name}.");
    }

    public function destroySearchItem(Request $request, ScreeningPackage $screeningPackage, SearchType $searchType): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        abort_unless(
            $screeningPackage->searchTypes()->where('search_types.id', $searchType->id)->exists(),
            404,
        );

        if ($screeningPackage->searchTypes()->count() <= 1) {
            return redirect()
                ->route('platform.packages.edit', $screeningPackage)
                ->withErrors(['search' => 'A package must include at least one search.']);
        }

        $screeningPackage->searchTypes()->detach($searchType->id);

        return redirect()
            ->route('platform.packages.edit', $screeningPackage)
            ->with('status', "{$searchType->name} removed from {$screeningPackage->name}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePackage(Request $request, ?ScreeningPackage $package = null): array
    {
        return array_merge(
            $this->validatePackageDetails($request, $package),
            $request->validate([
                'items' => ['required', 'array', 'min:1'],
                'items.*.search_type_id' => ['required', 'distinct', 'exists:search_types,id'],
                'items.*.data_source_id' => ['required', 'exists:data_sources,id'],
            ]),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePackageDetails(Request $request, ?ScreeningPackage $package = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                TenantRule::unique('screening_packages', 'slug')->ignore($package?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    /**
     * @return list<array{search_type_id: string, data_source_id: string}>
     */
    private function defaultFormItems(): array
    {
        return [['search_type_id' => '', 'data_source_id' => '']];
    }

    private function canManageCatalog(?User $user): bool
    {
        return $user !== null && $user->hasPermission(Permission::PlatformCatalogManage);
    }
}
