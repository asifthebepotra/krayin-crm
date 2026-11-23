<?php

return [
    'menu' => [
        'title' => 'CMS',
        'info'  => 'Manage your website content and publishing.',
    ],

    'acl' => [
        'title' => 'CMS',
    ],

    'general' => [
        'title'                  => 'Website CMS',
        'description'            => 'Manage website settings and open the native Statamic editor.',
        'statamic_ready'         => 'Statamic CMS is available.',
        'statamic_missing'       => 'Statamic CMS is not installed yet.',
        'control_panel_enabled'  => 'The Statamic Control Panel is enabled.',
        'control_panel_disabled' => 'The Statamic Control Panel is disabled.',
        'open_control_panel'     => 'Open Statamic Control Panel',
        'version'                => 'Installed version',
        'native_editor'          => 'Native Statamic editor',
        'native_editor_info'     => 'Pages, collections, assets, blueprints, navigation and publishing stay in Statamic.',
        'crm_integration'        => 'Krayin CRM integration',
        'crm_integration_info'   => 'Website enquiries will be connected to Krayin leads through the CMS integration layer.',
        'website_settings'       => 'Website settings',
        'website_settings_info'  => 'Configure the website identity, primary domain, locale and timezone.',
        'manage_settings'        => 'Manage website settings',
        'site_name'              => 'Site name',
        'primary_domain'         => 'Primary domain',
        'not_configured'         => 'Not configured',
        'installation_hint'      => 'Install the pinned Statamic dependency and run its installation command before opening the Control Panel.',
    ],

    'settings' => [
        'menu'                => 'Website settings',
        'title'               => 'Website settings',
        'description'         => 'Configure the primary website identity and regional defaults.',
        'back'                => 'Back to CMS',
        'site_name'           => 'Website name',
        'primary_domain'      => 'Primary domain',
        'primary_domain_help' => 'Enter the hostname only, without https:// or a path.',
        'default_locale'      => 'Default locale',
        'timezone'            => 'Timezone',
        'save'                => 'Save settings',
        'updated'             => 'Website settings have been saved.',
    ],

    'users' => [
        'menu'          => 'CMS Access',
        'title'         => 'CMS user access',
        'description'   => 'Grant active Krayin employees access to the Statamic Control Panel.',
        'notice'        => 'Employee accounts, passwords and CRM roles remain managed by Krayin. This screen only assigns a CMS role.',
        'employee'      => 'Employee',
        'status'        => 'Account status',
        'active'        => 'Active',
        'inactive'      => 'Inactive',
        'cms_role'      => 'CMS role',
        'no_access'     => 'No CMS access',
        'save'          => 'Save',
        'empty'         => 'No employees found.',
        'updated'       => 'CMS access has been updated.',
        'inactive_user' => 'CMS access cannot be granted to an inactive Krayin employee.',
    ],
];
