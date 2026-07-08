<?php

namespace Tests;

use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (app()->bound(TenantContext::class)) {
            app(TenantContext::class)->clear();
        }

        $this->initializeTenantContext();
    }

    protected function tearDown(): void
    {
        if (app()->bound(TenantContext::class)) {
            app(TenantContext::class)->clear();
        }

        parent::tearDown();
    }

    protected function initializeTenantContext(): void
    {
        $tenant = Tenant::query()->find(1)
            ?? Tenant::query()->where('slug', config('tenancy.default_slug'))->first();

        if ($tenant !== null) {
            app(TenantContext::class)->set($tenant);
        }
    }
}
