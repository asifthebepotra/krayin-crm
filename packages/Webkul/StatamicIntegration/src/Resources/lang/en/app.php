<?php

return [
    'menu' => [
        'title' => 'CMS',
        'info'  => 'Manage website content with Statamic',
    ],

    'acl' => [
        'title' => 'CMS',
    ],

    'dashboard' => [
        'title'             => 'Content Management System',
        'description'       => 'Manage your website content using the Statamic Control Panel.',
        'statamic_engine'   => 'Statamic CMS',
        'installed'         => 'Installed',
        'not_installed'     => 'Not installed',
        'control_panel'     => 'Control Panel',
        'enabled'           => 'Enabled',
        'disabled'          => 'Disabled',
        'content_api'       => 'Content API',
        'api_disabled_note' => 'The Content API remains disabled for this release. The native Control Panel and server-rendered website are used.',
        'open_cms'          => 'Open CMS Control Panel',
        'setup_title'       => 'Complete the Statamic installation',
        'setup_description' => 'The Krayin CMS module is registered, but the Statamic package has not been installed or discovered yet.',
        'minimum_version'   => 'Minimum supported version',
        'quick_links'       => 'CMS workspace',
        'collections'       => 'Collections and pages',
        'assets'            => 'Media library',
        'navigation'        => 'Navigation',
        'forms'             => 'Forms',
    ],

    'messages' => [
        'statamic_unavailable' => 'Statamic is not installed or its Control Panel is disabled.',
    ],
];