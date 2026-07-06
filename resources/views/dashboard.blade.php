<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Dashboard" subtitle="Operational overview and quick actions." />
    </x-slot>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="panel lg:col-span-2">
            <div class="panel-header">
                <h2 class="panel-title">Welcome, {{ $user->name }}</h2>
            </div>
            <div class="panel-body space-y-5">
                @if ($isPlatformView)
                    <p class="text-sm leading-relaxed text-enterprise-600">
                        Manage client organizations, platform staff accounts, and client support sessions from the administration section.
                    </p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-action-tile
                            href="{{ route('platform.clients.index') }}"
                            title="Client organizations"
                            description="Browse all client accounts, review users, and initiate support sessions."
                        />
                        <x-action-tile
                            href="{{ route('platform.users.index') }}"
                            title="Platform users"
                            description="Create and manage Saffhire staff with in-house access levels."
                        />
                    </div>
                @elseif ($organization)
                    <p class="text-sm leading-relaxed text-enterprise-600">
                        You are operating within <strong class="font-medium text-enterprise-900">{{ $organization->name }}</strong>.
                        Submit new screening orders or review completed report packages below.
                    </p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-action-tile
                            href="{{ route('reports.requests.create') }}"
                            title="New report request"
                            description="Submit a background screening order for processing."
                        />
                        <x-action-tile
                            href="{{ route('reports.index') }}"
                            title="Completed reports"
                            description="Review assembled report data for your organization."
                        />
                    </div>
                @else
                    <p class="text-sm leading-relaxed text-enterprise-600">
                        Your account is active but no organization context is available. Contact Saffhire support for access assignment.
                    </p>
                @endif
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Session details</h2>
            </div>
            <div class="panel-body space-y-4">
                <div>
                    <div class="meta-label">Signed in as</div>
                    <div class="meta-value">{{ $user->email }}</div>
                </div>
                @if ($organization)
                    <div>
                        <div class="meta-label">Organization</div>
                        <div class="meta-value">{{ $organization->name }}</div>
                    </div>
                @endif
                <a href="{{ route('profile.edit') }}" class="link-action">Manage my profile</a>
            </div>
        </div>
    </div>
</x-app-layout>
