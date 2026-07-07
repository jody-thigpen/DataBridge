<?php

namespace App\Services\DataSources\Drivers;

use App\Contracts\DataSources\DataSourceDriverContract;
use App\Models\DataSource;
use App\Services\DataSources\InformDataClient;

class InformDataDriver implements DataSourceDriverContract
{
    public function credentialFields(): array
    {
        return [
            [
                'key' => 'username',
                'label' => 'API username',
                'type' => 'text',
                'required' => true,
                'help' => 'InformData API credentials provided during onboarding.',
            ],
            [
                'key' => 'password',
                'label' => 'API password',
                'type' => 'password',
                'required' => true,
                'help' => 'Stored encrypted. Leave blank when editing to keep the current password.',
            ],
            [
                'key' => 'webhook_secret',
                'label' => 'Webhook secret (optional)',
                'type' => 'password',
                'required' => false,
                'help' => 'Used to validate incoming InformData webhook callbacks.',
            ],
        ];
    }

    public function normalizeCredentials(array $credentials, ?DataSource $existing = null): array
    {
        $normalized = [
            'username' => trim((string) ($credentials['username'] ?? '')),
        ];

        $password = (string) ($credentials['password'] ?? '');

        if ($password !== '') {
            $normalized['password'] = $password;
        } elseif ($existing !== null) {
            $normalized['password'] = $existing->config['password'] ?? '';
        } else {
            $normalized['password'] = '';
        }

        $webhookSecret = (string) ($credentials['webhook_secret'] ?? '');

        if ($webhookSecret !== '') {
            $normalized['webhook_secret'] = $webhookSecret;
        } elseif ($existing !== null && isset($existing->config['webhook_secret'])) {
            $normalized['webhook_secret'] = $existing->config['webhook_secret'];
        }

        return $normalized;
    }

    public function testConnection(DataSource $dataSource): array
    {
        return (new InformDataClient($dataSource))->testConnection();
    }
}
