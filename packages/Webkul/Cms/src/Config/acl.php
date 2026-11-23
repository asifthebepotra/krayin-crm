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
];
