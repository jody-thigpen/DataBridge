<?php

return [

    /*
    |--------------------------------------------------------------------------
    | InformData Continuous Monitoring API
    |--------------------------------------------------------------------------
    |
    | Credentials are stored encrypted on the data_sources record after seeding
    | or via Platform → Data sources. These env vars are optional shortcuts for
    | local/sandbox setup — see docs/ENV.md.
    |
    | API reference: https://api-monitoring.informdata.com/
    |
    */

    'base_url' => rtrim(env('INFORMDATA_BASE_URL', 'https://api-monitoring.informdata.com'), '/'),

    'documentation_url' => env('INFORMDATA_DOCUMENTATION_URL', 'https://api-monitoring.informdata.com/'),

    'username' => env('INFORMDATA_USERNAME'),

    'password' => env('INFORMDATA_PASSWORD'),

    'webhook_secret' => env('INFORMDATA_WEBHOOK_SECRET'),

    'auto_enable' => (bool) env('INFORMDATA_AUTO_ENABLE', false),

];
