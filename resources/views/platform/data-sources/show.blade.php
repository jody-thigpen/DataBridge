<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$dataSource->name" subtitle="External API connection for report data.">
            <x-slot name="actions">
                <a href="{{ route('platform.data-sources.index') }}" class="btn-secondary">Back to data sources</a>
                @if ($canManageDataSources)
                    <a href="{{ route('platform.data-sources.edit', $dataSource) }}" class="btn-primary">
                        {{ $dataSource->needsConfiguration() ? 'Configure connection' : 'Edit' }}
                    </a>
                    @unless ($dataSource->needsConfiguration())
                        <form method="POST" action="{{ route('platform.data-sources.test', $dataSource) }}">
                            @csrf
                            <button type="submit" class="btn-secondary">Test connection</button>
                        </form>
                    @endunless
                @endif
            </x-slot>
        </x-page-header>
    </x-slot>

    @if ($dataSource->needsConfiguration() && $canManageDataSources)
        <div class="alert-error mb-5">
            This data source has not been configured yet. Enter the API base URL, username, and password provided by InformData, then test the connection and enable it for report orders.
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="panel lg:col-span-1">
            <div class="panel-header">
                <h2 class="panel-title">Connection summary</h2>
            </div>
            <div class="panel-body space-y-4">
                <div>
                    <div class="meta-label">Provider</div>
                    <div class="meta-value">{{ $dataSource->driverEnum()->label() }}</div>
                </div>
                <div>
                    <div class="meta-label">Identifier</div>
                    <div class="meta-value">{{ $dataSource->slug }}</div>
                </div>
                <div>
                    <div class="meta-label">API base URL</div>
                    <div class="meta-value break-all">{{ $dataSource->displayBaseUrl() }}</div>
                </div>
                @if ($dataSource->documentation_url)
                    <div>
                        <div class="meta-label">Documentation</div>
                        <div class="meta-value">
                            <a href="{{ $dataSource->documentation_url }}" target="_blank" rel="noopener" class="link-action">View API docs</a>
                        </div>
                    </div>
                @endif
                <div>
                    <div class="meta-label">Status</div>
                    <div class="meta-value">
                        <span @class(['badge', 'badge-success' => $dataSource->is_active, 'badge-muted' => ! $dataSource->is_active])>
                            {{ $dataSource->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                <div>
                    <div class="meta-label">Last connection test</div>
                    <div class="meta-value">
                        @if ($dataSource->last_connected_at)
                            {{ $dataSource->last_connected_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                            <div class="mt-1 text-sm text-enterprise-600">{{ $dataSource->last_connection_message }}</div>
                        @else
                            Not tested yet
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="panel lg:col-span-2">
            <div class="panel-header">
                <h2 class="panel-title">Capabilities</h2>
            </div>
            <div class="panel-body space-y-4">
                @if ($dataSource->description)
                    <p class="text-sm leading-relaxed text-enterprise-600">{{ $dataSource->description }}</p>
                @endif

                <div class="flex flex-wrap gap-2">
                    @foreach ($dataSource->capabilityLabels() as $capability)
                        <span class="badge badge-success">{{ $capability }}</span>
                    @endforeach
                </div>

                @if ($dataSource->driverEnum() === \App\Enums\DataSourceDriver::InformData)
                    <div class="rounded-lg border border-enterprise-200 bg-enterprise-50 p-4 text-sm text-enterprise-700">
                        <p class="font-medium text-enterprise-900">InformData integration endpoints</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            <li><code>POST /token</code> — bearer token authentication</li>
                            <li><code>POST /api/IntegrationApi/AddMonitoringOrders</code> — enroll subjects</li>
                            <li><code>POST /api/IntegrationApi/ReportedMonitoringResults</code> — retrieve results</li>
                            <li><code>GET /api/IntegrationApi/GetCompanyProfiles</code> — connection test</li>
                            <li>Webhooks — real-time alerts on new records</li>
                        </ul>
                    </div>
                @endif

                @if ($canManageDataSources)
                    <div class="rounded-lg border border-enterprise-200 p-4">
                        <p class="text-sm font-medium text-enterprise-900">Configured credentials</p>
                        <p class="mt-1 text-sm text-enterprise-600">
                            Credentials are managed here in the admin — not in server environment files.
                        </p>
                        <dl class="mt-3 space-y-2 text-sm text-enterprise-700">
                            @foreach ($credentialFields as $field)
                                <div class="flex justify-between gap-4">
                                    <dt>{{ $field['label'] }}</dt>
                                    <dd class="font-mono text-enterprise-900">
                                        @if ($field['type'] === 'password')
                                            @if (($dataSource->config[$field['key']] ?? null) !== null && ($dataSource->config[$field['key']] ?? '') !== '')
                                                ••••••••
                                            @else
                                                —
                                            @endif
                                        @else
                                            {{ $dataSource->config[$field['key']] ?? '—' }}
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
