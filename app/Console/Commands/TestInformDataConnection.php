<?php

namespace App\Console\Commands;

use App\Models\DataSource;
use App\Services\DataSources\DataSourceManager;
use Illuminate\Console\Command;

class TestInformDataConnection extends Command
{
    protected $signature = 'informdata:test-connection {--slug=informdata-monitoring : Data source slug to test}';

    protected $description = 'Test the configured InformData Continuous Monitoring API connection';

    public function handle(DataSourceManager $dataSourceManager): int
    {
        $dataSource = DataSource::query()->where('slug', $this->option('slug'))->first();

        if ($dataSource === null) {
            $this->error('No data source found with slug "'.$this->option('slug').'". Run php artisan db:seed --class=InformDataDataSourceSeeder first.');

            return self::FAILURE;
        }

        if ($dataSource->needsConfiguration()) {
            $this->error('Data source "'.$dataSource->name.'" is not fully configured.');
            $this->line('Set INFORMDATA_USERNAME and INFORMDATA_PASSWORD in .env, then run:');
            $this->line('  php artisan db:seed --class=InformDataDataSourceSeeder');
            $this->line('Or configure credentials under Platform → Data sources → Edit.');

            if (blank($dataSource->base_url)) {
                $this->warn('Missing API base URL.');
            }

            if ($dataSource->needsCredentials()) {
                $this->warn('Missing API username or password.');
            }

            return self::FAILURE;
        }

        $this->info('Testing InformData connection to '.$dataSource->base_url.' ...');

        $result = $dataSourceManager->driver($dataSource)->testConnection($dataSource);

        $dataSource->update([
            'last_connected_at' => now(),
            'last_connection_status' => $result['success'] ? 'ok' : 'failed',
            'last_connection_message' => $result['message'],
        ]);

        if ($result['success']) {
            $this->info($result['message']);

            return self::SUCCESS;
        }

        $this->error($result['message']);

        return self::FAILURE;
    }
}
