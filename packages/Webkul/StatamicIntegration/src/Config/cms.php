<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Statamic Control Panel
    |--------------------------------------------------------------------------
    |
    | The Statamic package owns the actual CP route. This value is used as
    | a fallback while the Statamic configuration has not been published.
    |
    */
    'control_panel_path' => env('CP_ROUTE', 'cp'),

    /*
    |--------------------------------------------------------------------------
    | Minimum supported Statamic version
    |--------------------------------------------------------------------------
    */
    'minimum_statamic_version' => '6.42.0',

    /*
    |--------------------------------------------------------------------------
    | Content API
    |--------------------------------------------------------------------------
    |
    | The native Control Panel and server-rendered website are used by the
    | first release. Keep the Content API disabled unless it is explicitly
    | enabled and reviewed in config/statamic/api.php.
    |
    */
    'content_api_enabled' => false,
];