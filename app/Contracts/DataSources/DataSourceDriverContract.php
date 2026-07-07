<?php

namespace App\Contracts\DataSources;

use App\Models\DataSource;

interface DataSourceDriverContract
{
    /**
     * @return list<array{key: string, label: string, type: string, required: bool, help?: string}>
     */
    public function credentialFields(): array;

    /**
     * @param  array<string, mixed>  $credentials
     * @return array<string, mixed>
     */
    public function normalizeCredentials(array $credentials, ?DataSource $existing = null): array;

    /**
     * @return array{success: bool, message: string}
     */
    public function testConnection(DataSource $dataSource): array;
}
