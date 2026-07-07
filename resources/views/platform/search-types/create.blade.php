<x-app-layout>
    <x-slot name="header">
        <x-page-header title="New search type" subtitle="Define a screening search and assign its default data source.">
            <x-slot name="actions">
                <a href="{{ route('platform.search-types.index') }}" class="btn-secondary">Back to search types</a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <form method="POST" action="{{ route('platform.search-types.store') }}" class="panel max-w-2xl">
        @csrf
        <div class="panel-body space-y-4">
            <div>
                <x-input-label for="name" value="Display name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="code" value="Search code" />
                <select id="code" name="code" class="mt-1 block w-full" required>
                    @foreach ($codes as $code)
                        <option value="{{ $code->value }}" @selected(old('code') === $code->value)>{{ $code->label() }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="data_source_id" value="Data source" />
                <select id="data_source_id" name="data_source_id" class="mt-1 block w-full" required>
                    <option value="">Select data source</option>
                    @foreach ($dataSources as $dataSource)
                        <option value="{{ $dataSource->id }}" @selected(old('data_source_id') == $dataSource->id)>{{ $dataSource->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('data_source_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="description" value="Description" />
                <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('description') }}</textarea>
            </div>
            <div>
                <x-input-label for="sort_order" value="Sort order" />
                <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-1 block w-full" :value="old('sort_order', 0)" />
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-enterprise-300 text-brand-600 focus:ring-brand-500" @checked(old('is_active', true))>
                <x-input-label for="is_active" value="Active" class="mb-0" />
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="requires_review_before_submit" value="0">
                <input id="requires_review_before_submit" name="requires_review_before_submit" type="checkbox" value="1" class="rounded border-enterprise-300 text-brand-600 focus:ring-brand-500" @checked(old('requires_review_before_submit', true))>
                <x-input-label for="requires_review_before_submit" value="Require Saffhire review before submitting to vendor" class="mb-0" />
            </div>
            <x-primary-button>Create search type</x-primary-button>
        </div>
    </form>
</x-app-layout>
