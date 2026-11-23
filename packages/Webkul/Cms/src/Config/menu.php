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
];
