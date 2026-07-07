<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$package->name" subtitle="Screening package composition and pricing.">
            <x-slot name="actions">
                <a href="{{ route('platform.packages.index') }}" class="btn-secondary">Back to packages</a>
                @if ($canManageCatalog)
                    <a href="{{ route('platform.packages.edit', $package) }}" class="btn-primary">Edit</a>
                @endif
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="panel lg:col-span-1">
            <div class="panel-header"><h2 class="panel-title">Package summary</h2></div>
            <div class="panel-body space-y-4">
                <div>
                    <div class="meta-label">Identifier</div>
                    <div class="meta-value">{{ $package->slug }}</div>
                </div>
                <div>
                    <div class="meta-label">Base price</div>
                    <div class="meta-value">{{ $package->formattedBasePrice() }}</div>
                </div>
                <div>
                    <div class="meta-label">Status</div>
                    <div class="meta-value">
                        <span @class(['badge', 'badge-success' => $package->is_active, 'badge-muted' => ! $package->is_active])>
                            {{ $package->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                @if ($package->description)
                    <div>
                        <div class="meta-label">Description</div>
                        <div class="meta-value text-sm text-enterprise-700">{{ $package->description }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="panel lg:col-span-2">
            <div class="panel-header"><h2 class="panel-title">Included searches</h2></div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Search type</th>
                            <th>Code</th>
                            <th>Data source</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($package->searchTypes as $searchType)
                            <tr>
                                <td class="font-medium text-enterprise-900">{{ $searchType->name }}</td>
                                <td class="text-enterprise-600">{{ $searchType->code }}</td>
                                <td class="text-enterprise-600">{{ $dataSourcesById[$searchType->pivot->data_source_id] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-10 text-center text-enterprise-500">No searches assigned to this package.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="panel mt-5">
        <div class="panel-header"><h2 class="panel-title">Assigned clients</h2></div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Base price</th>
                        <th>Client price</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($package->organizations as $organization)
                        @php
                            $override = $clientPricesByOrganizationId[$organization->id] ?? null;
                            $effectivePrice = $override !== null
                                ? '$'.number_format((float) $override, 2)
                                : $package->formattedBasePrice();
                        @endphp
                        <tr>
                            <td class="font-medium text-enterprise-900">
                                <a href="{{ route('platform.clients.show', $organization) }}" class="link-action">{{ $organization->name }}</a>
                            </td>
                            <td class="text-enterprise-600">{{ $package->formattedBasePrice() }}</td>
                            <td class="text-enterprise-600">{{ $effectivePrice }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-10 text-center text-enterprise-500">This package is not assigned to any clients yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
