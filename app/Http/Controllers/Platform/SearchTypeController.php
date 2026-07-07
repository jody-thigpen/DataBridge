<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Permission;
use App\Enums\SearchTypeCode;
use App\Http\Controllers\Controller;
use App\Models\DataSource;
use App\Models\SearchType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SearchTypeController extends Controller
{
    public function index(Request $request): View
    {
        $searchTypes = SearchType::query()
            ->with('dataSource')
            ->orderBy('sort_order')
            ->paginate(20);

        return view('platform.search-types.index', [
            'searchTypes' => $searchTypes,
            'canManageCatalog' => $this->canManageCatalog($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        return view('platform.search-types.create', [
            'dataSources' => DataSource::query()->orderBy('name')->get(),
            'codes' => SearchTypeCode::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:search_types,slug'],
            'code' => ['required', Rule::enum(SearchTypeCode::class), 'unique:search_types,code'],
            'data_source_id' => ['required', 'exists:data_sources,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $code = SearchTypeCode::from($validated['code']);

        $searchType = SearchType::query()->create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? null,
            'code' => $code->value,
            'data_source_id' => $validated['data_source_id'],
            'description' => $validated['description'] ?? $code->defaultDescription(),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('platform.search-types.index')
            ->with('status', "{$searchType->name} created.");
    }

    public function edit(Request $request, SearchType $searchType): View
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        return view('platform.search-types.edit', [
            'searchType' => $searchType,
            'dataSources' => DataSource::query()->orderBy('name')->get(),
            'codes' => SearchTypeCode::cases(),
        ]);
    }

    public function update(Request $request, SearchType $searchType): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('search_types', 'slug')->ignore($searchType->id)],
            'code' => ['required', Rule::enum(SearchTypeCode::class), Rule::unique('search_types', 'code')->ignore($searchType->id)],
            'data_source_id' => ['required', 'exists:data_sources,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $searchType->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'code' => SearchTypeCode::from($validated['code'])->value,
            'data_source_id' => $validated['data_source_id'],
            'description' => $validated['description'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return redirect()
            ->route('platform.search-types.index')
            ->with('status', "{$searchType->name} updated.");
    }

    private function canManageCatalog(?User $user): bool
    {
        return $user !== null && $user->hasPermission(Permission::PlatformCatalogManage);
    }
}
