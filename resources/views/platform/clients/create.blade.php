<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="New client organization"
            subtitle="Create a client company and its initial administrator account."
        >
            <x-slot name="actions">
                <a href="{{ route('platform.clients.index') }}" class="btn-secondary">Back to clients</a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <form method="POST" action="{{ route('platform.clients.store') }}" class="grid gap-5 lg:grid-cols-2">
        @csrf

        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Organization details</h2>
            </div>
            <div class="panel-body space-y-4">
                <div>
                    <x-input-label for="name" value="Company name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="slug" value="URL identifier (optional)" />
                    <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug')" />
                    <p class="mt-1 text-xs text-enterprise-500">Leave blank to generate from the company name.</p>
                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="parent_id" value="Parent company (optional)" />
                    <select id="parent_id" name="parent_id" class="mt-1 block w-full">
                        <option value="">None — standalone client</option>
                        @foreach ($parentOrganizations as $parent)
                            <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-enterprise-300 text-brand-600 focus:ring-brand-500" @checked(old('is_active', true))>
                    <x-input-label for="is_active" value="Organization is active" class="mb-0" />
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Initial client admin</h2>
            </div>
            <div class="panel-body space-y-4">
                <p class="text-sm text-enterprise-600">
                    This person will be the first administrator for the organization and can invite additional team members.
                </p>
                <div>
                    <x-input-label for="admin_name" value="Full name" />
                    <x-text-input id="admin_name" name="admin_name" class="mt-1 block w-full" :value="old('admin_name')" required />
                    <x-input-error :messages="$errors->get('admin_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="admin_email" value="Work email" />
                    <x-text-input id="admin_email" name="admin_email" type="email" class="mt-1 block w-full" :value="old('admin_email')" required />
                    <x-input-error :messages="$errors->get('admin_email')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="admin_password" value="Temporary password" />
                    <x-text-input id="admin_password" name="admin_password" type="password" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('admin_password')" class="mt-2" />
                </div>
                <x-primary-button>Create client organization</x-primary-button>
            </div>
        </div>
    </form>
</x-app-layout>
