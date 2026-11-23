<?php

return [
    [
        'key'        => 'cms',
        'name'       => 'cms::app.menu.title',
        'info'       => 'cms::app.menu.info',
        'route'      => 'admin.cms.index',
        'sort'       => 8,
        'icon-class' => 'icon-setting',
    ],
    [
        'key'        => 'cms.settings',
        'name'       => 'cms::app.settings.menu',
        'route'      => 'admin.cms.settings.edit',
        'sort'       => 1,
        'icon-class' => '',
    ],
    [
        'key'        => 'cms.users',
        'name'       => 'cms::app.users.menu',
        'route'      => 'admin.cms.users.index',
        'sort'       => 2,
        'icon-class' => '',
    ],
    [
        'key'        => 'cms.submissions',
        'name'       => 'cms::app.submissions.menu',
        'route'      => 'admin.cms.submissions.index',
        'sort'       => 3,
        'icon-class' => '',
    ],
    [
        'key'        => 'cms.themes',
        'name'       => 'cms::app.themes.menu',
        'route'      => 'admin.cms.themes.index',
        'sort'       => 4,
        'icon-class' => '',
    ],
];
