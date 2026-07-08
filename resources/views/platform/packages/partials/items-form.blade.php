@php
    $dataSources = \App\Models\DataSource::query()->orderBy('name')->get();
    $normalizedFormItems = collect($formItems)->values()->map(function (array $item, int $index): array {
        return [
            '_key' => $index + 1,
            'search_type_id' => (string) ($item['search_type_id'] ?? ''),
            'data_source_id' => (string) ($item['data_source_id'] ?? ''),
        ];
    })->all();
    $nextItemKey = count($normalizedFormItems) + 1;
    $searchTypeDefaults = $searchTypes->mapWithKeys(
        fn ($type) => [(string) $type->id => $type->data_source_id !== null ? (string) $type->data_source_id : '']
    );
    $firstSearchTypeId = (string) ($searchTypes->first()?->id ?? '');
@endphp

<div x-data="{
    items: @js($normalizedFormItems),
    nextKey: @js($nextItemKey),
    searchTypeDefaults: @js($searchTypeDefaults),
    addItem() {
        const firstTypeId = @js($firstSearchTypeId);
        this.items.push({
            _key: this.nextKey++,
            search_type_id: firstTypeId,
            data_source_id: this.searchTypeDefaults[firstTypeId] ?? '',
        });
    },
    removeItem(index) {
        if (this.items.length <= 1) {
            return;
        }

        this.items.splice(index, 1);
    },
    applyDefaultDataSource(index) {
        const typeId = String(this.items[index].search_type_id);
        if (Object.prototype.hasOwnProperty.call(this.searchTypeDefaults, typeId)) {
            this.items[index].data_source_id = this.searchTypeDefaults[typeId];
        }
    },
}">
    <div class="space-y-3">
        <div
            x-for="(item, index) in items"
            :key="item._key"
            class="grid gap-3 rounded-lg border border-enterprise-200 p-4 md:grid-cols-[1fr_1fr_auto]"
        >
            <div>
                <label class="text-sm font-medium text-enterprise-700">Search type</label>
                <select
                    class="mt-1 block w-full"
                    :name="`items[${index}][search_type_id]`"
                    x-model="item.search_type_id"
                    @change="applyDefaultDataSource(index)"
                    required
                >
                    <option value="">Select search type</option>
                    @foreach ($searchTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-enterprise-700">Data source</label>
                <select
                    class="mt-1 block w-full"
                    :name="`items[${index}][data_source_id]`"
                    x-model="item.data_source_id"
                    required
                >
                    <option value="">Select data source</option>
                    @foreach ($dataSources as $source)
                        <option value="{{ $source->id }}">{{ $source->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button
                    type="button"
                    class="btn-secondary whitespace-nowrap"
                    @click="removeItem(index)"
                    :disabled="items.length <= 1"
                    :class="{ 'cursor-not-allowed opacity-50': items.length <= 1 }"
                >
                    Remove
                </button>
            </div>
        </div>
    </div>

    <button type="button" class="btn-secondary mt-3" @click="addItem()">Add search to package</button>
    <p class="mt-2 text-sm text-enterprise-600">Each package must include at least one search.</p>
    <x-input-error :messages="$errors->get('items')" class="mt-2" />
    <x-input-error :messages="$errors->get('items.*.search_type_id')" class="mt-2" />
</div>
