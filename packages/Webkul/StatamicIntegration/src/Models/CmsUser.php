<?php

namespace Webkul\StatamicIntegration\Models;

use Webkul\User\Models\User as KrayinUser;

/**
 * Krayin employee identity with the additional attributes Statamic's
 * Eloquent user adapter expects.
 *
 * The model continues to use Krayin's users table, role relationship,
 * status flag, password and Sanctum traits.
 */
class CmsUser extends KrayinUser
{
    protected $casts = [
        'preferences' => 'array',
        'last_login'  => 'datetime',
    ];
}