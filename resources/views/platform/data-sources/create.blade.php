@php
    $selectedDriver = old('driver', \App\Enums\DataSourceDriver::InformData->value);
    $driverEnum = \App\Enums\DataSourceDriver::from($selectedDriver);
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="New data source"
            subtitle="Connect an external API for submitting requests and receiving report data."
        >
            <x-slot name="actions">
                <a href="{{ route('platform.data-sources.index') }}" class="btn-secondary">Back to data sources</a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <form method="POST" action="{{ route('platform.data-sources.store') }}" class="grid gap-5 lg:grid-cols-2">
        @csrf

        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Connection details</h2>
            </div>
            <div class="panel-body space-y-4">
                <div>
                    <x-input-label for="name" value="Display name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', 'InformData Continuous Monitoring')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="slug" value="Identifier (optional)" />
                    <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug', 'informdata-monitoring')" />
                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="driver" value="Provider" />
                    <select id="driver" name="driver" class="mt-1 block w-full">
                        @foreach ($drivers as $driver)
                            <option value="{{ $driver->value }}" @selected($selectedDriver === $driver->value)>{{ $driver->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('driver')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="base_url" value="API base URL" />
                    <x-text-input id="base_url" name="base_url" type="url" class="mt-1 block w-full" :value="old('base_url')" placeholder="https://your-informdata-base-url" required />
                    <p class="mt-1 text-xs text-enterprise-500">InformData provides this BASE_URL during onboarding. Token requests go to <code>/token</code>.</p>
                    <x-input-error :messages="$errors->get('base_url')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="documentation_url" value="Documentation URL" />
                    <x-text-input id="documentation_url" name="documentation_url" type="url" class="mt-1 block w-full" :value="old('documentation_url', $driverEnum->documentationUrl())" />
                    <x-input-error :messages="$errors->get('documentation_url')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('description', $driverEnum->defaultDescription()) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-enterprise-300 text-brand-600 focus:ring-brand-500" @checked(old('is_active'))>
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
                    InformData uses OAuth-style bearer tokens. Credentials are exchanged at <code>/token</code> with <code>grant_type=password</code>, then used for Integration API calls.
                </p>
                <div>
                    <x-input-label for="username" value="API username" />
                    <x-text-input id="username" name="username" class="mt-1 block w-full" :value="old('username')" required />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="password" value="API password" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="webhook_secret" value="Webhook secret (optional)" />
                    <x-text-input id="webhook_secret" name="webhook_secret" type="password" class="mt-1 block w-full" />
                    <p class="mt-1 text-xs text-enterprise-500">For validating InformData webhook callbacks when new records are reported.</p>
                    <x-input-error :messages="$errors->get('webhook_secret')" class="mt-2" />
                </div>
                <x-primary-button>Create data source</x-primary-button>
            </div>
        </div>
    </form>
</x-app-layout>
