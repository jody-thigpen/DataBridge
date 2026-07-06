<x-app-layout>
    <x-slot name="header">
        <x-page-header title="My profile" subtitle="Account credentials and personal settings." />
    </x-slot>

    <div class="space-y-5">
        <div class="panel max-w-3xl">
            <div class="panel-header">
                <h2 class="panel-title">Profile information</h2>
            </div>
            <div class="panel-body max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="panel max-w-3xl">
            <div class="panel-header">
                <h2 class="panel-title">Password</h2>
            </div>
            <div class="panel-body max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="panel max-w-3xl">
            <div class="panel-header">
                <h2 class="panel-title">Account deactivation</h2>
            </div>
            <div class="panel-body max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
