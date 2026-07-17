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
                <x-input-label for="candidate_email" value="Candidate email" />
                <x-text-input id="candidate_email" name="candidate_email" type="email" class="mt-1 block w-full" :value="old('candidate_email')" required />
                <p class="mt-1 text-sm text-enterprise-500">
                    An intake form link will be emailed to the candidate so they can provide history details and authorization.
                </p>
                <x-input-error :messages="$errors->get('candidate_email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="candidate_phone" value="Candidate phone (optional)" />
                <x-text-input id="candidate_phone" name="candidate_phone" class="mt-1 block w-full" :value="old('candidate_phone')" />
                <x-input-error :messages="$errors->get('candidate_phone')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="screening_package_id" value="Screening package" />
                <select id="screening_package_id" name="screening_package_id" class="mt-1 block w-full" required>
                    <option value="">Select package</option>
                    @forelse ($packages as $package)
                        <option value="{{ $package->id }}" @selected(old('screening_package_id') == $package->id)>
                            {{ $package->name }} — {{ $package->formattedPriceForOrganization($organization) }}
                        </option>
                    @empty
                        <option value="" disabled>No active packages available</option>
                    @endforelse
                </select>
                <x-input-error :messages="$errors->get('screening_package_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="notes" value="Internal order notes (optional)" />
                <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full">{{ old('notes') }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3 border-t border-enterprise-200 pt-4">
                <x-primary-button>Submit request &amp; email candidate</x-primary-button>
                <a href="{{ route('reports.index') }}" class="link-action">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
