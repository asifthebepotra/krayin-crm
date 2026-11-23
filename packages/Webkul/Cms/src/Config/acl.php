<?php

return [
    [
        'key'   => 'cms',
        'name'  => 'cms::app.acl.title',
        'route' => 'admin.cms.index',
        'sort'  => 8,
    ],
    [
        'key'   => 'cms.settings',
        'name'  => 'cms::app.settings.menu',
        'route' => ['admin.cms.settings.edit', 'admin.cms.settings.update'],
        'sort'  => 1,
    ],
    [
        'key'   => 'cms.users',
        'name'  => 'cms::app.users.menu',
        'route' => ['admin.cms.users.index', 'admin.cms.users.update'],
        'sort'  => 2,
    ],
    [
        'key'   => 'cms.submissions',
        'name'  => 'cms::app.submissions.menu',
        'route' => 'admin.cms.submissions.index',
        'sort'  => 3,
    ],
    [
        'key'   => 'cms.themes',
        'name'  => 'cms::app.themes.menu',
        'route' => [
            'admin.cms.themes.index',
            'admin.cms.themes.store',
            'admin.cms.themes.activate',
            'admin.cms.themes.destroy',
        ],
        'sort'  => 4,
    ],
];
