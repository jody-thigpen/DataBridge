<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="New report request"
            :subtitle="'Submit a screening order for ' . $organization->name"
        />
    </x-slot>

    <div class="panel max-w-3xl">
        <form method="POST" action="{{ route('reports.requests.store') }}" class="panel-body space-y-5">
            @csrf

            <div>
                <x-input-label for="subject_name" value="Subject legal name" />
                <x-text-input id="subject_name" name="subject_name" class="mt-1 block w-full" :value="old('subject_name')" required />
                <x-input-error :messages="$errors->get('subject_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="package" value="Screening package" />
                <select id="package" name="package" class="mt-1 block w-full" required>
                    <option value="">Select package</option>
                    <option value="standard_criminal" @selected(old('package') === 'standard_criminal')>Standard criminal</option>
                    <option value="employment_verification" @selected(old('package') === 'employment_verification')>Employment verification</option>
                    <option value="comprehensive" @selected(old('package') === 'comprehensive')>Comprehensive package</option>
                </select>
                <x-input-error :messages="$errors->get('package')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="notes" value="Internal order notes (optional)" />
                <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full">{{ old('notes') }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3 border-t border-enterprise-200 pt-4">
                <x-primary-button>Submit request</x-primary-button>
                <a href="{{ route('reports.index') }}" class="link-action">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
