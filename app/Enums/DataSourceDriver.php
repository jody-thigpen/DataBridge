<?php

namespace App\Enums;

enum DataSourceDriver: string
{
    case InformData = 'informdata';

    public function label(): string
    {
        return match ($this) {
            self::InformData => 'InformData Continuous Monitoring',
        };
    }

    public function documentationUrl(): ?string
    {
        return match ($this) {
            self::InformData => config('informdata.documentation_url'),
        };
    }

    public function defaultBaseUrl(): ?string
    {
        return match ($this) {
            self::InformData => config('informdata.base_url'),
        };
    }

    public function defaultDescription(): string
    {
        return match ($this) {
            self::InformData => 'InformData Continuous Background Check API for enrolling subjects, retrieving monitoring results, and receiving webhook alerts on new records.',
        };
    }

    /**
     * @return list<string>
     */
    public function defaultCapabilities(): array
    {
        return match ($this) {
            self::InformData => [
                'continuous_monitoring',
                'submit_orders',
                'receive_results',
                'webhooks',
            ],
        };
    }
}
