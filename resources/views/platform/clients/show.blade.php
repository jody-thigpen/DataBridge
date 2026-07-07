<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$organization->name" subtitle="Client organization record and support actions.">
            <x-slot name="actions">
                <a href="{{ route('platform.clients.index') }}" class="btn-secondary">Back to clients</a>
                <form method="POST" action="{{ route('platform.clients.enter', $organization) }}">
                    @csrf
                    <button type="submit" class="btn-primary">Enter organization</button>
                </form>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="panel lg:col-span-1">
            <div class="panel-header">
                <h2 class="panel-title">Organization details</h2>
            </div>
            <div class="panel-body space-y-4">
                <div>
                    <div class="meta-label">Identifier</div>
                    <div class="meta-value">{{ $organization->slug }}</div>
                </div>
                <div>
                    <div class="meta-label">Parent company</div>
                    <div class="meta-value">{{ $organization->parent?->name ?? 'None' }}</div>
                </div>
                <div>
                    <div class="meta-label">Status</div>
                    <div class="meta-value">
                        <span @class(['badge', 'badge-success' => $organization->is_active, 'badge-muted' => ! $organization->is_active])>
                            {{ $organization->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                @if ($organization->children->isNotEmpty())
                    <div>
                        <div class="meta-label">Subsidiaries</div>
                        <ul class="mt-1 list-disc space-y-1 pl-5 text-sm text-enterprise-700">
                            @foreach ($organization->children as $child)
                                <li>{{ $child->name }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        <div @class(['panel', 'lg:col-span-2' => ! $canManageClients, 'lg:col-span-1' => $canManageClients])>
            <div class="panel-header">
                <h2 class="panel-title">Assigned users</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="text-right">Support</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($organization->roleAssignments as $assignment)
                            <tr>
                                <td class="font-medium text-enterprise-900">{{ $assignment->user->name }}</td>
                                <td class="text-enterprise-600">{{ $assignment->user->email }}</td>
                                <td class="text-enterprise-600">{{ $assignment->role->name }}</td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('platform.impersonation.store', $assignment->user) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="link-action">Start support session</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center text-enterprise-500">No users assigned to this organization.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($canManageClients)
            <div class="panel lg:col-span-1">
                <div class="panel-header">
                    <h2 class="panel-title">Add team member</h2>
                </div>
                <form method="POST" action="{{ route('platform.clients.users.store', $organization) }}" class="panel-body space-y-4">
                    @csrf
                    <p class="text-sm text-enterprise-600">
                        Provision additional administrators or employee accounts for this client.
                    </p>
                    <div>
                        <x-input-label for="name" value="Full name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Work email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="password" value="Temporary password" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="role_id" value="Organization role" />
                        <select id="role_id" name="role_id" class="mt-1 block w-full">
                            @foreach ($organizationRoles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                    </div>
                    <x-primary-button>Add member</x-primary-button>
                </form>
            </div>
        @endif
    </div>

    @if ($canManageCatalog ? $allPackages->isNotEmpty() : $assignedPackages->isNotEmpty())
        <div class="panel mt-5">
            <div class="panel-header">
                <h2 class="panel-title">Assigned packages</h2>
            </div>
            @if ($canManageCatalog)
                <form method="POST" action="{{ route('platform.clients.package-prices.update', $organization) }}">
                    @csrf
                    @method('PATCH')
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Assigned</th>
                                    <th>Package</th>
                                    <th>Base price</th>
                                    <th>Client override</th>
                                    <th>Effective price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($allPackages as $package)
                                    @php
                                        $isAssigned = in_array($package->id, $assignedPackageIds, true);
                                        $override = $package->organizationPrices->first();
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="hidden" name="packages[{{ $loop->index }}][screening_package_id]" value="{{ $package->id }}">
                                            <input
                                                type="checkbox"
                                                name="packages[{{ $loop->index }}][assigned]"
                                                value="1"
                                                class="rounded border-enterprise-300 text-brand-600 focus:ring-brand-500"
                                                @checked(old('packages.'.$loop->index.'.assigned', $isAssigned))
                                            />
                                        </td>
                                        <td class="font-medium text-enterprise-900">{{ $package->name }}</td>
                                        <td class="text-enterprise-600">{{ $package->formattedBasePrice() }}</td>
                                        <td>
                                            <x-text-input
                                                name="packages[{{ $loop->index }}][price]"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                class="block w-full max-w-[10rem]"
                                                :value="old('packages.'.$loop->index.'.price', $override?->price)"
                                                placeholder="Use base price"
                                            />
                                        </td>
                                        <td class="text-enterprise-600">
                                            {{ $isAssigned ? $package->formattedPriceForOrganization($organization) : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-enterprise-200 px-4 py-3">
                        <p class="mb-3 text-sm text-enterprise-600">
                            Check packages to make them available to this client. Leave the override blank to use the base price.
                        </p>
                        <x-primary-button>Save assignments and pricing</x-primary-button>
                    </div>
                </form>
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Package</th>
                                <th>Base price</th>
                                <th>Client price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($assignedPackages as $package)
                                <tr>
                                    <td class="font-medium text-enterprise-900">{{ $package->name }}</td>
                                    <td class="text-enterprise-600">{{ $package->formattedBasePrice() }}</td>
                                    <td class="text-enterprise-600">{{ $package->formattedPriceForOrganization($organization) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @elseif ($canManageCatalog)
        <div class="panel mt-5">
            <div class="panel-header">
                <h2 class="panel-title">Assigned packages</h2>
            </div>
            <div class="panel-body text-sm text-enterprise-600">
                No active packages are available. Create a package first, then assign it to this client.
            </div>
        </div>
    @endif

    @if ($canManageCatalog && $searchTypes->isNotEmpty())
        <div class="panel mt-5">
            <div class="panel-header">
                <h2 class="panel-title">Search review overrides</h2>
            </div>
            <form method="POST" action="{{ route('platform.clients.search-review-settings.update', $organization) }}">
                @csrf
                @method('PATCH')
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Search type</th>
                                <th>Default behavior</th>
                                <th>Client override</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($searchTypes as $searchType)
                                @php
                                    $override = $searchTypeSettings->get($searchType->id);
                                    $selected = old('settings.'.$loop->index.'.requires_review_before_submit', $override === null ? 'default' : ($override->requires_review_before_submit ? '1' : '0'));
                                @endphp
                                <tr>
                                    <td class="font-medium text-enterprise-900">{{ $searchType->name }}</td>
                                    <td class="text-enterprise-600">{{ $searchType->requires_review_before_submit ? 'Require review' : 'Auto-submit' }}</td>
                                    <td>
                                        <input type="hidden" name="settings[{{ $loop->index }}][search_type_id]" value="{{ $searchType->id }}">
                                        <select name="settings[{{ $loop->index }}][requires_review_before_submit]" class="block w-full max-w-xs">
                                            <option value="default" @selected($selected === 'default')>Use search default</option>
                                            <option value="1" @selected($selected === '1')>Require review</option>
                                            <option value="0" @selected($selected === '0')>Auto-submit</option>
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-enterprise-200 px-4 py-3">
                    <p class="mb-3 text-sm text-enterprise-600">Override whether each search requires Saffhire review before vendor submission for this client.</p>
                    <x-primary-button>Save review overrides</x-primary-button>
                </div>
            </form>
        </div>
    @endif
</x-app-layout>
