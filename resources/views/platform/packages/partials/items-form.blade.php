@php
    $dataSources = \App\Models\DataSource::query()->orderBy('name')->get();
@endphp

<div x-data="{
    items: @js($formItems),
    searchTypes: @js($searchTypes->map(fn ($type) => [
        'id' => $type->id,
        'name' => $type->name,
        'default_data_source_id' => $type->data_source_id,
    ])),
    dataSources: @js($dataSources->map(fn ($source) => ['id' => $source->id, 'name' => $source->name])),
    addItem() {
        const firstType = this.searchTypes[0] ?? null;
        this.items.push({
            search_type_id: firstType ? String(firstType.id) : '',
            data_source_id: firstType ? String(firstType.default_data_source_id) : '',
        });
    },
    applyDefaultDataSource(index) {
        const type = this.searchTypes.find((entry) => String(entry.id) === String(this.items[index].search_type_id));
        if (type) {
            this.items[index].data_source_id = String(type.default_data_source_id);
        }
    },
}">
    <div class="space-y-3">
        <template x-for="(item, index) in items" :key="index">
            <div class="grid gap-3 rounded-lg border border-enterprise-200 p-4 md:grid-cols-2">
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
                        <template x-for="type in searchTypes" :key="type.id">
                            <option :value="type.id" x-text="type.name"></option>
                        </template>
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
                        <template x-for="source in dataSources" :key="source.id">
                            <option :value="source.id" x-text="source.name"></option>
                        </template>
                    </select>
                </div>
            </div>
        </template>
    </div>

    <button type="button" class="btn-secondary mt-3" @click="addItem()">Add search to package</button>
    <x-input-error :messages="$errors->get('items')" class="mt-2" />
    <x-input-error :messages="$errors->get('items.*.search_type_id')" class="mt-2" />
</div>
