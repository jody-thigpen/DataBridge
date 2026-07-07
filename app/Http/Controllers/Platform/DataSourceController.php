<?php

namespace App\Http\Controllers\Platform;

use App\Enums\DataSourceDriver;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\DataSource;
use App\Models\User;
use App\Services\DataSources\DataSourceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DataSourceController extends Controller
{
    public function __construct(
        private readonly DataSourceManager $dataSourceManager,
    ) {}

    public function index(Request $request): View
    {
        $dataSources = DataSource::query()
            ->orderBy('name')
            ->paginate(20);

        return view('platform.data-sources.index', [
            'dataSources' => $dataSources,
            'canManageDataSources' => $this->canManageDataSources($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->canManageDataSources($request->user()), 403);

        return view('platform.data-sources.create', [
            'drivers' => $this->dataSourceManager->availableDrivers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canManageDataSources($request->user()), 403);

        $driver = $this->dataSourceManager->assertDriver(
            (string) $request->input('driver', DataSourceDriver::InformData->value),
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:data_sources,slug'],
            'driver' => ['required', Rule::enum(DataSourceDriver::class)],
            'base_url' => ['required', 'url', 'max:255'],
            'documentation_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $driverInstance = $this->dataSourceManager->driverForType($driver);
        $config = $driverInstance->normalizeCredentials([
            'username' => $validated['username'],
            'password' => $validated['password'],
            'webhook_secret' => $validated['webhook_secret'] ?? null,
        ]);

        $dataSource = DataSource::query()->create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? null,
            'driver' => $driver->value,
            'base_url' => rtrim($validated['base_url'], '/'),
            'documentation_url' => $validated['documentation_url'] ?? $driver->documentationUrl(),
            'description' => $validated['description'] ?? $driver->defaultDescription(),
            'capabilities' => $driver->defaultCapabilities(),
            'config' => $config,
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return redirect()
            ->route('platform.data-sources.show', $dataSource)
            ->with('status', "{$dataSource->name} created. Test the connection before enabling it for report requests.");
    }

    public function show(Request $request, DataSource $dataSource): View
    {
        $canManage = $this->canManageDataSources($request->user());

        return view('platform.data-sources.show', [
            'dataSource' => $dataSource,
            'canManageDataSources' => $canManage,
            'credentialFields' => $canManage
                ? $this->dataSourceManager->driver($dataSource)->credentialFields()
                : [],
        ]);
    }

    public function edit(Request $request, DataSource $dataSource): View
    {
        abort_unless($this->canManageDataSources($request->user()), 403);

        return view('platform.data-sources.edit', [
            'dataSource' => $dataSource,
            'credentialFields' => $this->dataSourceManager->driver($dataSource)->credentialFields(),
        ]);
    }

    public function update(Request $request, DataSource $dataSource): RedirectResponse
    {
        abort_unless($this->canManageDataSources($request->user()), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('data_sources', 'slug')->ignore($dataSource->id)],
            'base_url' => ['required', 'url', 'max:255'],
            'documentation_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $driverInstance = $this->dataSourceManager->driver($dataSource);
        $config = $driverInstance->normalizeCredentials([
            'username' => $validated['username'],
            'password' => $validated['password'] ?? '',
            'webhook_secret' => $validated['webhook_secret'] ?? null,
        ], $dataSource);

        $dataSource->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'base_url' => rtrim($validated['base_url'], '/'),
            'documentation_url' => $validated['documentation_url'],
            'description' => $validated['description'],
            'config' => $config,
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return redirect()
            ->route('platform.data-sources.show', $dataSource)
            ->with('status', "{$dataSource->name} updated.");
    }

    public function test(DataSource $dataSource): RedirectResponse
    {
        abort_unless($this->canManageDataSources(request()->user()), 403);

        if ($dataSource->needsConfiguration()) {
            return back()->with('error', 'Configure the API base URL and credentials before testing the connection.');
        }

        $result = $this->dataSourceManager->driver($dataSource)->testConnection($dataSource);

        $dataSource->update([
            'last_connected_at' => now(),
            'last_connection_status' => $result['success'] ? 'ok' : 'failed',
            'last_connection_message' => $result['message'],
        ]);

        return back()->with(
            $result['success'] ? 'status' : 'error',
            $result['message'],
        );
    }

    private function canManageDataSources(?User $user): bool
    {
        return $user !== null && $user->hasPermission(Permission::PlatformDataSourcesManage);
    }
}
