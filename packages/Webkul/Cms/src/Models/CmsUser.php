<?php

namespace Webkul\Cms\Models;

use Webkul\User\Models\User;

class CmsUser extends User
{
    /**
     * Statamic and Krayin share the employees table. This subclass adds only
     * the CMS-specific casts while preserving the existing Krayin model API.
     */
    protected $table = 'users';

    protected $casts = [
        'preferences' => 'array',
        'last_login'  => 'datetime',
    ];
}
