<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default tenant
    |--------------------------------------------------------------------------
    |
    | Used when the request host is the base application domain (no tenant
    | subdomain). Production installs should set DEFAULT_TENANT_SLUG to the
    | primary operator slug.
    |
    */

    'default_slug' => env('DEFAULT_TENANT_SLUG', 'saffhire'),

    /*
    |--------------------------------------------------------------------------
    | Base application domains
    |--------------------------------------------------------------------------
    |
    | Hostnames that serve the app without a tenant subdomain. Requests to
    | these hosts resolve to the default tenant above.
    |
    */

    'base_domains' => array_values(array_filter(array_map(
        fn (string $domain) => trim($domain),
        explode(',', env('TENANCY_BASE_DOMAINS', 'localhost,127.0.0.1,databridge.saffhiresecure.com')),
    ))),

];
