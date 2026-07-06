<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6">
        <h2 class="text-xl font-semibold text-enterprise-900">Sign in</h2>
        <p class="mt-1 text-sm text-enterprise-600">Enter your credentials to access the platform.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Work email" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-enterprise-300 text-accent focus:ring-accent" name="remember">
                <span class="ms-2 text-sm text-enterprise-600">Keep me signed in</span>
            </label>

            @if (Route::has('password.request'))
                <a class="link-action" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center">
            Sign in
        </x-primary-button>
    </form>
</x-guest-layout>
