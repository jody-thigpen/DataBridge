<?php

namespace App\Services\DataSources;

use App\Contracts\DataSources\DataSourceDriverContract;
use App\Enums\DataSourceDriver;
use App\Models\DataSource;
use App\Services\DataSources\Drivers\InformDataDriver;
use InvalidArgumentException;

class DataSourceManager
{
    public function driver(DataSource $dataSource): DataSourceDriverContract
    {
        return match ($dataSource->driverEnum()) {
            DataSourceDriver::InformData => app(InformDataDriver::class),
        };
    }

    public function driverForType(DataSourceDriver $driver): DataSourceDriverContract
    {
        return match ($driver) {
            DataSourceDriver::InformData => app(InformDataDriver::class),
        };
    }

    /**
     * @return list<DataSourceDriver>
     */
    public function availableDrivers(): array
    {
        return DataSourceDriver::cases();
    }

    public function assertDriver(string $driver): DataSourceDriver
    {
        return DataSourceDriver::tryFrom($driver)
            ?? throw new InvalidArgumentException("Unsupported data source driver [{$driver}].");
    }
}
