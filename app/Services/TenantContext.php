<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class TenantContext
{
    public const SESSION_KEY = 'tenant_id';

    private ?Tenant $resolvedTenant = null;

    public function id(): ?int
    {
        return $this->current()?->id;
    }

    public function current(): ?Tenant
    {
        return $this->resolvedTenant;
    }

    public function set(Tenant $tenant): void
    {
        $this->resolvedTenant = $tenant;
        Session::put(self::SESSION_KEY, $tenant->id);
    }

    public function clear(): void
    {
        $this->resolvedTenant = null;
        Session::forget(self::SESSION_KEY);
    }

    public function resolve(Request $request): Tenant
    {
        if ($this->resolvedTenant !== null) {
            return $this->resolvedTenant;
        }

        $slug = $this->resolveSlugFromHost($request->getHost());

        $tenant = Tenant::query()
            ->when(
                $slug !== null,
                fn ($query) => $query->where('slug', $slug),
                fn ($query) => $query->where('slug', config('tenancy.default_slug')),
            )
            ->where('is_active', true)
            ->first();

        if ($tenant === null) {
            abort(404, 'This tenant is not available.');
        }

        $this->resolvedTenant = $tenant;

        return $tenant;
    }

    public function resolveSlugFromHost(string $host): ?string
    {
        $host = strtolower($host);

        foreach (config('tenancy.base_domains', []) as $baseDomain) {
            $baseDomain = strtolower($baseDomain);

            if ($host === $baseDomain) {
                return null;
            }

            $suffix = '.'.$baseDomain;

            if (str_ends_with($host, $suffix)) {
                $subdomain = substr($host, 0, -strlen($suffix));

                if ($subdomain !== '' && $subdomain !== 'www') {
                    return $subdomain;
                }

                return null;
            }
        }

        return null;
    }
}
