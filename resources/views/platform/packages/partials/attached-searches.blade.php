<div class="space-y-4">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Search type</th>
                    <th>Data source</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($package->searchTypes as $searchType)
                    <tr>
                        <td class="font-medium text-enterprise-900">
                            <a href="{{ route('platform.search-types.edit', $searchType) }}" class="link-action">
                                {{ $searchType->name }}
                            </a>
                        </td>
                        <td class="text-enterprise-600">
                            {{ $dataSourcesById[$searchType->pivot->data_source_id] ?? '—' }}
                        </td>
                        <td class="text-right">
                            <form
                                method="POST"
                                action="{{ route('platform.packages.searches.destroy', [$package, $searchType]) }}"
                                class="inline"
                                onsubmit="return confirm('Remove {{ $searchType->name }} from this package?');"
                            >
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="link-action text-red-700"
                                    @disabled($package->searchTypes->count() <= 1)
                                >
                                    Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-10 text-center text-enterprise-500">
                            No searches attached to this package yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($package->searchTypes->count() <= 1)
        <p class="text-sm text-enterprise-600">A package must include at least one search.</p>
    @endif

    @if ($availableSearchTypes->isNotEmpty())
        <form method="POST" action="{{ route('platform.packages.searches.store', $package) }}" class="space-y-4 border-t border-enterprise-200 pt-4">
            @csrf
            <h3 class="text-sm font-semibold text-enterprise-900">Add search to package</h3>
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <x-input-label for="search_type_id" value="Search type" />
                    <select id="search_type_id" name="search_type_id" class="mt-1 block w-full" required>
                        <option value="">Select search type</option>
                        @foreach ($availableSearchTypes as $type)
                            <option
                                value="{{ $type->id }}"
                                @selected(old('search_type_id') == $type->id)
                                data-default-source="{{ $type->data_source_id }}"
                            >
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('search_type_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="data_source_id" value="Data source" />
                    <select id="data_source_id" name="data_source_id" class="mt-1 block w-full" required>
                        <option value="">Select data source</option>
                        @foreach ($dataSources as $source)
                            <option value="{{ $source->id }}" @selected(old('data_source_id') == $source->id)>
                                {{ $source->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('data_source_id')" class="mt-2" />
                </div>
            </div>
            <x-primary-button>Add search</x-primary-button>
        </form>
    @elseif ($package->searchTypes->isNotEmpty())
        <p class="border-t border-enterprise-200 pt-4 text-sm text-enterprise-600">
            All active search types are already included in this package.
        </p>
    @endif

    <x-input-error :messages="$errors->get('search')" class="mt-2" />
</div>

<script>
    document.getElementById('search_type_id')?.addEventListener('change', (event) => {
        const option = event.target.selectedOptions[0];
        const defaultSourceId = option?.dataset.defaultSource;
        const dataSourceSelect = document.getElementById('data_source_id');

        if (defaultSourceId && dataSourceSelect) {
            dataSourceSelect.value = defaultSourceId;
        }
    });
</script>
