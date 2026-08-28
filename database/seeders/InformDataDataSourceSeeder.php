<?php

namespace Database\Seeders;

use App\Enums\DataSourceDriver;
use App\Models\DataSource;
use Illuminate\Database\Seeder;

class InformDataDataSourceSeeder extends Seeder
{
    public function run(): void
    {
        $driver = DataSourceDriver::InformData;
        $configFromEnv = $this->configFromEnvironment();

        $defaults = [
            'name' => 'InformData Continuous Monitoring',
            'driver' => $driver->value,
            'base_url' => $driver->defaultBaseUrl(),
            'documentation_url' => $driver->documentationUrl(),
            'description' => $driver->defaultDescription(),
            'capabilities' => $driver->defaultCapabilities(),
            'config' => $configFromEnv,
            'is_active' => $configFromEnv !== null && config('informdata.auto_enable'),
        ];

        $existing = DataSource::query()->where('slug', 'informdata-monitoring')->first();

        if ($existing === null) {
            DataSource::query()->create(array_merge($defaults, ['slug' => 'informdata-monitoring']));

            return;
        }

        $updates = [];

        if (blank($existing->base_url)) {
            $updates['base_url'] = $defaults['base_url'];
        }

        if (blank($existing->documentation_url)) {
            $updates['documentation_url'] = $defaults['documentation_url'];
        }

        if (blank($existing->description)) {
            $updates['description'] = $defaults['description'];
        }

        if ($existing->config === null && $configFromEnv !== null) {
            $updates['config'] = $configFromEnv;

            if (config('informdata.auto_enable')) {
                $updates['is_active'] = true;
            }
        }

        if ($updates !== []) {
            $existing->update($updates);
        }
    }

    /**
     * @return array<string, string>|null
     */
    private function configFromEnvironment(): ?array
    {
        $username = config('informdata.username');
        $password = config('informdata.password');

        if (blank($username) || blank($password)) {
            return null;
        }

        return array_filter([
            'username' => $username,
            'password' => $password,
            'webhook_secret' => config('informdata.webhook_secret'),
        ], fn ($value) => filled($value));
    }
}
