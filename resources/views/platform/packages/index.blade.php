<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Screening packages" subtitle="Assemble search types into priced packages for client orders.">
            @if ($canManageCatalog)
                <x-slot name="actions">
                    <a href="{{ route('platform.packages.create') }}" class="btn-primary">New package</a>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>

    <div class="panel">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Package</th>
                        <th>Searches</th>
                        <th>Clients</th>
                        <th>Base price</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packages as $package)
                        <tr>
                            <td class="font-medium text-enterprise-900">{{ $package->name }}</td>
                            <td class="text-enterprise-600">{{ $package->search_types_count }}</td>
                            <td class="text-enterprise-600">{{ $package->organizations_count }}</td>
                            <td class="text-enterprise-600">{{ $package->formattedBasePrice() }}</td>
                            <td>
                                <span @class(['badge', 'badge-success' => $package->is_active, 'badge-muted' => ! $package->is_active])>
                                    {{ $package->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('platform.packages.show', $package) }}" class="link-action">View details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-enterprise-500">No packages configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $packages->links() }}</div>
</x-app-layout>
