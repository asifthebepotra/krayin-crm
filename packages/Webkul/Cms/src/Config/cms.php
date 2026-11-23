<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Statamic Control Panel
    |--------------------------------------------------------------------------
    |
    | The Statamic Control Panel remains the native CMS editing interface.
    | This package only provides the Krayin entry point and integration
    | services around it.
    |
    */

    'control_panel_path' => env('STATAMIC_CP_ROUTE', 'cp'),

    /*
    |--------------------------------------------------------------------------
    | CRM Homepage
    |--------------------------------------------------------------------------
    |
    | Public website routing is owned by Statamic. Krayin's default root
    | redirect must be reviewed when Statamic is enabled.
    |
    */

    'public_homepage' => 'statamic',
];
