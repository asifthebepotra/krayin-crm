<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Statamic Control Panel
    |--------------------------------------------------------------------------
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

    /*
    |--------------------------------------------------------------------------
    | Statamic Forms -> Krayin CRM
    |--------------------------------------------------------------------------
    |
    | Each enabled form maps its own Statamic field handles to the standard
    | CRM intake fields. Set enabled to false to store submissions in
    | Statamic without creating a Krayin lead.
    |
    */
    'forms' => [
        'contact' => [
            'enabled' => true,
            'create_lead' => true,
            'field_map' => [
                'name' => 'name',
                'email' => 'email',
                'phone' => 'phone',
                'company' => 'company',
                'message' => 'message',
            ],
            'lead_title_prefix' => 'Website enquiry',
            'source_name' => 'Website',
            'type_name' => null,
            'pipeline_id' => null,
            'source_id' => null,
            'type_id' => null,
            'owner_id' => null,
        ],
    ],
];