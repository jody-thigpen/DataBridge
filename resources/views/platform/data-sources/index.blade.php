<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Data sources"
            subtitle="Configure external APIs for submitting screening orders and receiving report data."
        >
            @if ($canManageDataSources)
                <x-slot name="actions">
                    <a href="{{ route('platform.data-sources.create') }}" class="btn-primary">New data source</a>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>

    <div class="panel min-w-0">
        @unless ($canManageDataSources)
            <div class="border-b border-enterprise-200 bg-enterprise-50 px-5 py-3 text-sm text-enterprise-700">
                You can view data source connections here. Editing requires a Platform Admin or Operations account.
            </div>
        @endunless
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        @if ($canManageDataSources)
                            <th class="w-24"></th>
                        @endif
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
                            <td class="font-medium text-enterprise-900">
                                <a href="{{ route('platform.data-sources.show', $dataSource) }}" class="link-action">{{ $dataSource->name }}</a>
                            </td>
                            @if ($canManageDataSources)
                                <td>
                                    <a href="{{ route('platform.data-sources.edit', $dataSource) }}" class="btn-secondary !px-3 !py-1.5 text-xs">Edit</a>
                                </td>
                            @endif
                            <td class="max-w-[12rem] truncate text-enterprise-600" title="{{ $dataSource->driverEnum()->label() }}">{{ $dataSource->driverEnum()->label() }}</td>
                            <td class="max-w-[14rem] truncate text-enterprise-600" title="{{ $dataSource->displayBaseUrl() }}">{{ $dataSource->displayBaseUrl() }}</td>
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
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('platform.data-sources.show', $dataSource) }}" class="link-action">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManageDataSources ? 7 : 6 }}" class="py-10 text-center text-enterprise-500">
                                No data sources configured.
                                @if ($canManageDataSources)
                                    <a href="{{ route('platform.data-sources.create') }}" class="link-action">Add one</a>
                                    or run <code class="text-xs">php artisan db:seed</code> to load the default InformData connection.
                                @endif
                            </td>
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
