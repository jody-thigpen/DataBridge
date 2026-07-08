<?php

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoClientSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = Tenant::query()->where('slug', config('tenancy.default_slug'))->value('id') ?? 1;

        $organization = Organization::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'slug' => env('DEMO_CLIENT_ORG_SLUG', 'demo-client-co')],
            [
                'name' => env('DEMO_CLIENT_ORG_NAME', 'Demo Client Company'),
                'is_active' => true,
            ],
        );

        $email = env('DEMO_CLIENT_ADMIN_EMAIL', 'demo-admin@demo-client.test');
        $password = env('DEMO_CLIENT_ADMIN_PASSWORD', 'DemoClient2026!');

        $admin = User::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'email' => $email],
            [
                'name' => env('DEMO_CLIENT_ADMIN_NAME', 'Demo Client Admin'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_active' => true,
                'current_organization_id' => $organization->id,
            ],
        );

        $admin->assignRole(OrganizationRole::ClientAdmin, $organization);
    }
}
