<?php

namespace App\Models\Concerns;

trait UsesDataConnection
{
    public function getConnectionName(): ?string
    {
        return config('database.data_connection', 'data');
    }
}
