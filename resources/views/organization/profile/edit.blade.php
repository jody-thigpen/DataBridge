<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Organization profile"
            :subtitle="'Company information for ' . $organization->name"
        />
    </x-slot>

    <div class="panel max-w-3xl">
        <form method="POST" action="{{ route('organization.profile.update') }}" class="panel-body space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <x-input-label for="name" value="Legal organization name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $organization->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label value="System identifier" />
                <div class="mt-1 text-sm text-enterprise-600">{{ $organization->slug }}</div>
            </div>

            <x-primary-button>Save changes</x-primary-button>
        </form>
    </div>
</x-app-layout>
