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
            self::InformData => 'https://api-monitoring.informdata.com/',
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
