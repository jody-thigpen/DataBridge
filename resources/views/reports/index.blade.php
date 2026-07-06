<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Reports"
            :subtitle="'Completed screening packages for ' . $organization->name"
        >
            <x-slot name="actions">
                <a href="{{ route('reports.requests.create') }}" class="btn-primary">New report request</a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="panel">
        <div class="panel-body">
            <div class="rounded border border-dashed border-enterprise-300 bg-enterprise-50 px-6 py-12 text-center">
                <h2 class="text-base font-semibold text-enterprise-900">Report registry</h2>
                <p class="mx-auto mt-2 max-w-2xl text-sm leading-relaxed text-enterprise-600">
                    Completed reports will be listed here with filtering, sorting, and export once vendor integrations are connected.
                    This view is designed to support high-volume tabular data review.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
