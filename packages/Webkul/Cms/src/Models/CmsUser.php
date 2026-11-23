<?php

namespace Webkul\Cms\Models;

use Webkul\User\Models\User;

class CmsUser extends User
{
    /**
     * Statamic and Krayin share the employees table. This subclass adds only
     * Statamic-specific casts while keeping Krayin's employee model intact.
     */
    protected $table = 'users';

    protected $casts = [
        'super'       => 'boolean',
        'preferences' => 'array',
        'last_login'  => 'datetime',
    ];
}
