<x-app-layout>
    <x-slot name="header">
        <x-page-header title="New screening package" subtitle="Combine search types into a package with a base price.">
            <x-slot name="actions">
                <a href="{{ route('platform.packages.index') }}" class="btn-secondary">Back to packages</a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <form method="POST" action="{{ route('platform.packages.store') }}" class="grid gap-5 lg:grid-cols-2">
        @csrf

        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Package details</h2></div>
            <div class="panel-body space-y-4">
                <div>
                    <x-input-label for="name" value="Package name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="slug" value="Identifier (optional)" />
                    <x-text-input id="slug" name="slug" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="base_price" value="Base price (USD)" />
                    <x-text-input id="base_price" name="base_price" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('base_price')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-enterprise-300 text-brand-600 focus:ring-brand-500" checked>
                    <x-input-label for="is_active" value="Active" class="mb-0" />
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Included searches</h2></div>
            <div class="panel-body">
                <p class="mb-4 text-sm text-enterprise-600">Each search in the package can route to its own data source connection.</p>
                @include('platform.packages.partials.items-form', ['searchTypes' => $searchTypes, 'formItems' => $formItems])
            </div>
        </div>

        <div class="lg:col-span-2">
            <x-primary-button>Create package</x-primary-button>
        </div>
    </form>
</x-app-layout>
