<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateAllDatabases extends Command
{
    protected $signature = 'migrate:all {--force : Force the operation to run in production}';

    protected $description = 'Run migrations for the app and secured data databases';

    public function handle(): int
    {
        $this->info('Migrating app database...');
        $appExit = $this->call('migrate', array_filter([
            '--force' => $this->option('force'),
        ]));

        if ($appExit !== self::SUCCESS) {
            return $appExit;
        }

        $dataConnection = config('database.data_connection', 'data');

        $this->info("Migrating data database ({$dataConnection})...");
        $dataExit = $this->call('migrate', array_filter([
            '--database' => $dataConnection,
            '--path' => 'database/migrations/data',
            '--force' => $this->option('force'),
        ]));

        return $dataExit;
    }
}
