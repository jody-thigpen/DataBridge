<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\ScreeningPackage;
use App\Models\SearchType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScreeningPackageController extends Controller
{
    public function index(Request $request): View
    {
        $packages = ScreeningPackage::query()
            ->withCount('searchTypes')
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
        $screeningPackage->load(['searchTypes']);

        $dataSourcesById = \App\Models\DataSource::query()
            ->whereIn('id', $screeningPackage->searchTypes->pluck('pivot.data_source_id'))
            ->pluck('name', 'id');

        return view('platform.packages.show', [
            'package' => $screeningPackage,
            'dataSourcesById' => $dataSourcesById,
            'canManageCatalog' => $this->canManageCatalog(request()->user()),
        ]);
    }

    public function edit(Request $request, ScreeningPackage $screeningPackage): View
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        $screeningPackage->load(['searchTypes.dataSource']);

        return view('platform.packages.edit', [
            'package' => $screeningPackage,
            'searchTypes' => SearchType::query()->with('dataSource')->where('is_active', true)->orderBy('sort_order')->get(),
            'formItems' => $this->formItemsFromPackage($screeningPackage),
        ]);
    }

    public function update(Request $request, ScreeningPackage $screeningPackage): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        $validated = $this->validatePackage($request, $screeningPackage);

        $screeningPackage->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'base_price' => $validated['base_price'],
            'is_active' => $validated['is_active'] ?? false,
        ]);

        $screeningPackage->syncSearchItems($validated['items']);

        return redirect()
            ->route('platform.packages.show', $screeningPackage)
            ->with('status', "{$screeningPackage->name} updated.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePackage(Request $request, ?ScreeningPackage $package = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('screening_packages', 'slug')->ignore($package?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.search_type_id' => ['required', 'distinct', 'exists:search_types,id'],
            'items.*.data_source_id' => ['required', 'exists:data_sources,id'],
        ]);
    }

    /**
     * @return list<array{search_type_id: string, data_source_id: string}>
     */
    private function defaultFormItems(): array
    {
        return [['search_type_id' => '', 'data_source_id' => '']];
    }

    /**
     * @return list<array{search_type_id: int, data_source_id: int}>
     */
    private function formItemsFromPackage(ScreeningPackage $package): array
    {
        $items = $package->searchTypes->map(fn (SearchType $searchType) => [
            'search_type_id' => $searchType->id,
            'data_source_id' => $searchType->pivot->data_source_id,
        ])->all();

        return $items !== [] ? $items : $this->defaultFormItems();
    }

    private function canManageCatalog(?User $user): bool
    {
        return $user !== null && $user->hasPermission(Permission::PlatformCatalogManage);
    }
}
