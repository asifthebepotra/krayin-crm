<?php

namespace Webkul\Cms\Models;

use Illuminate\Database\Eloquent\Model;

class CmsSetting extends Model
{
    protected $table = 'cms_settings';

    protected $fillable = [
        'site_name',
        'primary_domain',
        'default_locale',
        'timezone',
    ];
}
