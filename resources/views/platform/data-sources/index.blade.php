<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Data sources"
            subtitle="Configure external APIs for submitting screening requests and receiving report data."
        >
            @if ($canManageDataSources)
                <x-slot name="actions">
                    <a href="{{ route('platform.data-sources.create') }}" class="btn-primary">New data source</a>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>

    <div class="panel">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Driver</th>
                        <th>API base URL</th>
                        <th>Connection</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dataSources as $dataSource)
                        <tr>
                            <td class="font-medium text-enterprise-900">{{ $dataSource->name }}</td>
                            <td class="text-enterprise-600">{{ $dataSource->driverEnum()->label() }}</td>
                            <td class="text-enterprise-600">{{ $dataSource->displayBaseUrl() }}</td>
                            <td>
                                @if ($dataSource->needsConfiguration())
                                    <span class="badge badge-muted">Needs setup</span>
                                @elseif ($dataSource->last_connected_at)
                                    <span @class(['badge', 'badge-success' => $dataSource->isConnected(), 'badge-muted' => ! $dataSource->isConnected()])>
                                        {{ $dataSource->isConnected() ? 'Verified' : 'Failed' }}
                                    </span>
                                @else
                                    <span class="badge badge-muted">Not tested</span>
                                @endif
                            </td>
                            <td>
                                <span @class(['badge', 'badge-success' => $dataSource->is_active, 'badge-muted' => ! $dataSource->is_active])>
                                    {{ $dataSource->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right space-x-3">
                                @if ($canManageDataSources && $dataSource->needsConfiguration())
                                    <a href="{{ route('platform.data-sources.edit', $dataSource) }}" class="link-action">Configure</a>
                                @else
                                    <a href="{{ route('platform.data-sources.show', $dataSource) }}" class="link-action">View details</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-enterprise-500">No data sources configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $dataSources->links() }}
    </div>
</x-app-layout>
