<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="($dataSource->needsConfiguration() ? 'Configure ' : 'Edit ') . $dataSource->name" subtitle="Enter the API base URL and credentials provided by your data vendor.">
            <x-slot name="actions">
                <a href="{{ route('platform.data-sources.show', $dataSource) }}" class="btn-secondary">Cancel</a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <form method="POST" action="{{ route('platform.data-sources.update', $dataSource) }}" class="grid gap-5 lg:grid-cols-2">
        @csrf
        @method('PATCH')

        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Connection details</h2>
            </div>
            <div class="panel-body space-y-4">
                <div>
                    <x-input-label for="name" value="Display name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $dataSource->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="slug" value="Identifier" />
                    <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug', $dataSource->slug)" required />
                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                </div>
                <div>
                    <x-input-label value="Provider" />
                    <div class="mt-1 text-sm text-enterprise-700">{{ $dataSource->driverEnum()->label() }}</div>
                </div>
                <div>
                    <x-input-label for="base_url" value="API base URL" />
                    <x-text-input id="base_url" name="base_url" type="url" class="mt-1 block w-full" :value="old('base_url', $dataSource->base_url)" required />
                    <x-input-error :messages="$errors->get('base_url')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="documentation_url" value="Documentation URL" />
                    <x-text-input id="documentation_url" name="documentation_url" type="url" class="mt-1 block w-full" :value="old('documentation_url', $dataSource->documentation_url)" />
                    <x-input-error :messages="$errors->get('documentation_url')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('description', $dataSource->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-enterprise-300 text-brand-600 focus:ring-brand-500" @checked(old('is_active', $dataSource->is_active))>
                    <x-input-label for="is_active" value="Enable for report orders" class="mb-0" />
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">API credentials</h2>
            </div>
            <div class="panel-body space-y-4">
                <p class="text-sm text-enterprise-600">
                    All connection settings are stored in the database and can be updated here at any time. Changes take effect immediately for new report orders.
                </p>
                @foreach ($credentialFields as $field)
                    <div>
                        <x-input-label :for="$field['key']" :value="$field['label']" />
                        <x-text-input
                            :id="$field['key']"
                            :name="$field['key']"
                            :type="$field['type']"
                            class="mt-1 block w-full"
                            :value="$field['type'] === 'password' ? '' : old($field['key'], $dataSource->config[$field['key']] ?? '')"
                            :required="$field['required'] && $field['type'] !== 'password'"
                        />
                        @if ($field['type'] === 'password' && ($dataSource->config[$field['key']] ?? '') !== '')
                            <p class="mt-1 text-xs text-enterprise-500">Leave blank to keep the current value.</p>
                        @endif
                        @if (! empty($field['help']))
                            <p class="mt-1 text-xs text-enterprise-500">{{ $field['help'] }}</p>
                        @endif
                        <x-input-error :messages="$errors->get($field['key'])" class="mt-2" />
                    </div>
                @endforeach
                <x-primary-button>Save changes</x-primary-button>
            </div>
        </div>
    </form>
</x-app-layout>
