<?php

namespace App\Models;

use App\Enums\DataSourceDriver;
use App\Models\Concerns\BelongsToTenant;
use App\Services\DataSources\DataSourceManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DataSource extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'driver',
        'base_url',
        'documentation_url',
        'description',
        'capabilities',
        'config',
        'is_active',
        'last_connected_at',
        'last_connection_status',
        'last_connection_message',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'config' => 'encrypted:array',
            'is_active' => 'boolean',
            'last_connected_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DataSource $dataSource): void {
            if (blank($dataSource->slug)) {
                $dataSource->slug = Str::slug($dataSource->name);
            }
        });
    }

    public function driverEnum(): DataSourceDriver
    {
        return DataSourceDriver::from($this->driver);
    }

    public function driverInstance(): object
    {
        return app(DataSourceManager::class)->driver($this);
    }

    public function isConnected(): bool
    {
        return $this->last_connection_status === 'ok';
    }

    public function needsConfiguration(): bool
    {
        $config = $this->config ?? [];

        return blank($this->base_url)
            || blank($config['username'] ?? null)
            || blank($config['password'] ?? null);
    }

    public function displayBaseUrl(): string
    {
        return $this->needsConfiguration() ? 'Not configured' : $this->base_url;
    }

    /**
     * @return list<string>
     */
    public function capabilityLabels(): array
    {
        return collect($this->capabilities ?? [])
            ->map(fn (string $capability) => str($capability)->headline()->toString())
            ->all();
    }
}
