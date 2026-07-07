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

        DataSource::query()->firstOrCreate(
            ['slug' => 'informdata-monitoring'],
            [
                'name' => 'InformData Continuous Monitoring',
                'driver' => $driver->value,
                'base_url' => '',
                'documentation_url' => $driver->documentationUrl(),
                'description' => $driver->defaultDescription(),
                'capabilities' => $driver->defaultCapabilities(),
                'config' => null,
                'is_active' => false,
            ],
        );
    }
}
