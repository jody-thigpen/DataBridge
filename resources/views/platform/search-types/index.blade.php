<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Search types" subtitle="Individual screening searches mapped to vendor data sources.">
            @if ($canManageCatalog)
                <x-slot name="actions">
                    <a href="{{ route('platform.search-types.create') }}" class="btn-primary">New search type</a>
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
                        <th>Code</th>
                        <th>Data source</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($searchTypes as $searchType)
                        <tr>
                            <td class="font-medium text-enterprise-900">{{ $searchType->name }}</td>
                            <td class="text-enterprise-600">{{ $searchType->code }}</td>
                            <td class="text-enterprise-600">{{ $searchType->dataSource->name }}</td>
                            <td>
                                <span @class(['badge', 'badge-success' => $searchType->is_active, 'badge-muted' => ! $searchType->is_active])>
                                    {{ $searchType->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right">
                                @if ($canManageCatalog)
                                    <a href="{{ route('platform.search-types.edit', $searchType) }}" class="link-action">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-enterprise-500">No search types configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $searchTypes->links() }}</div>
</x-app-layout>
