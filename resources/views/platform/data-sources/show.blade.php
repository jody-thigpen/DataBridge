<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$dataSource->name" subtitle="External API connection for report data.">
            <x-slot name="actions">
                <a href="{{ route('platform.data-sources.index') }}" class="btn-secondary">Back to data sources</a>
                @if ($canManageDataSources)
                    <a href="{{ route('platform.data-sources.edit', $dataSource) }}" class="btn-primary">Edit</a>
                    @if ($dataSource->isReadyToTest())
                        <form method="POST" action="{{ route('platform.data-sources.test', $dataSource) }}">
                            @csrf
                            <button type="submit" class="btn-secondary">Test connection</button>
                        </form>
                    @endif
                @endif
            </x-slot>
        </x-page-header>
    </x-slot>

    @if ($canManageDataSources && $dataSource->driverEnum() === \App\Enums\DataSourceDriver::InformData && $dataSource->needsConfiguration())
        <div class="panel mb-5">
            <div class="panel-header">
                <h2 class="panel-title">Setup checklist</h2>
            </div>
            <div class="panel-body space-y-3 text-sm text-enterprise-700">
                <ol class="list-decimal space-y-2 pl-5">
                    <li @class(['text-enterprise-900 font-medium' => ! blank($dataSource->base_url)])>
                        API base URL is set to the Continuous Monitoring host
                        @unless (blank($dataSource->base_url))
                            (<code>{{ $dataSource->base_url }}</code>)
                        @endunless
                    </li>
                    <li @class(['text-enterprise-900 font-medium' => ! $dataSource->needsCredentials()])>
                        Enter the InformData API username and password
                    </li>
                    <li @class(['text-enterprise-900 font-medium' => $dataSource->isConnected()])>
                        Run <strong>Test connection</strong> to verify access
                    </li>
                    <li @class(['text-enterprise-900 font-medium' => $dataSource->is_active])>
                        Enable the connection for report orders
                    </li>
                </ol>
                <p class="text-enterprise-600">
                    Reference:
                    <a href="{{ $dataSource->documentation_url }}" target="_blank" rel="noopener" class="link-action">InformData Continuous Monitoring API</a>.
                    Optional: set <code>INFORMDATA_USERNAME</code> and <code>INFORMDATA_PASSWORD</code> in <code>.env</code>, then run
                    <code>php artisan db:seed --class=InformDataDataSourceSeeder</code>.
                </p>
            </div>
        </div>
    @endif

    @if ($canManageDataSources)
        <div class="panel mb-5">
            <div class="panel-body flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-enterprise-700">
                    @if ($dataSource->needsConfiguration())
                        @if ($dataSource->needsCredentials())
                            Add your InformData API credentials to finish setup.
                        @else
                            Credentials are saved. Test the connection, then enable this source.
                        @endif
                    @else
                        Update the API base URL, documentation link, or credentials for this vendor connection.
                    @endif
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('platform.data-sources.edit', $dataSource) }}" class="btn-primary shrink-0">Edit connection</a>
                    @if ($dataSource->isReadyToTest())
                        <form method="POST" action="{{ route('platform.data-sources.test', $dataSource) }}">
                            @csrf
                            <button type="submit" class="btn-secondary">Test connection</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if ($dataSource->needsConfiguration() && $canManageDataSources)
        <div class="alert-error mb-5">
            @if ($dataSource->needsCredentials())
                Enter the InformData API username and password, then test the connection and enable this source for report orders.
            @else
                Credentials are saved. Test the connection, then enable this source for report orders.
            @endif
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
