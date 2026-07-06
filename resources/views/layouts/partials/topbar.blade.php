<header class="app-topbar">
    <div class="flex h-14 items-center justify-between gap-4 px-4 sm:px-6">
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                class="inline-flex items-center justify-center rounded border border-enterprise-300 p-2 text-enterprise-700 hover:bg-enterprise-50 lg:hidden"
                @click="sidebarOpen = !sidebarOpen"
                aria-label="Toggle navigation"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            @if (auth()->user() && auth()->user()->accessibleOrganizations()->count() > 1)
                <form method="POST" action="{{ route('organization.switch') }}" class="hidden min-w-0 sm:block">
                    @csrf
                    <label class="sr-only" for="organization_id">Organization</label>
                    <select
                        id="organization_id"
                        name="organization_id"
                        class="max-w-xs text-sm"
                        onchange="this.form.submit()"
                    >
                        @foreach (auth()->user()->accessibleOrganizations() as $organization)
                            <option
                                value="{{ $organization->id }}"
                                @selected(($currentOrganization->id ?? null) === $organization->id)
                            >
                                {{ $organization->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @elseif ($currentOrganization ?? null)
                <div class="hidden items-center gap-3 sm:flex">
                    <div class="truncate text-sm font-medium text-enterprise-700">
                        {{ $currentOrganization->name }}
                    </div>
                    @if (auth()->user()->isPlatformUser() && ! ($isImpersonating ?? false))
                        <form method="POST" action="{{ route('organization.exit') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-secondary !py-1.5 text-xs">
                                Exit client view
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
                <div class="text-sm font-medium text-enterprise-900">{{ auth()->user()->name }}</div>
                <div class="text-xs text-enterprise-500">{{ auth()->user()->email }}</div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-secondary !py-1.5 text-xs">
                    Sign out
                </button>
            </form>
        </div>
    </div>
</header>
