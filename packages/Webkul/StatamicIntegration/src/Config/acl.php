<?php

return [
    [
        'key'   => 'cms',
        'name'  => 'statamic_integration::app.acl.title',
        'route' => [
            'admin.cms.index',
            'admin.cms.open',
            'admin.cms.themes.index',
            'admin.cms.themes.store',
            'admin.cms.themes.activate',
            'admin.cms.themes.destroy',
        ],
        'sort'  => 8,
    ],
];