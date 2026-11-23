<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Statamic Control Panel
    |--------------------------------------------------------------------------
    |
    | Statamic remains the native website editor. The Krayin CMS module
    | links to its Control Panel and provides the integration layer.
    |
    */

    'control_panel_path' => env('STATAMIC_CP_ROUTE', 'cp'),

    /*
    |--------------------------------------------------------------------------
    | Public Homepage
    |--------------------------------------------------------------------------
    */

    'public_homepage' => 'statamic',

    /*
    |--------------------------------------------------------------------------
    | CMS Roles
    |--------------------------------------------------------------------------
    |
    | These role handles must match the keys in resources/users/roles.yaml.
    | CRM roles and CMS roles are intentionally managed independently.
    |
    */

    'roles' => [
        'cms_admin'  => 'CMS Administrator',
        'cms_editor' => 'Content Editor',
        'cms_author' => 'Content Author',
    ],
];
