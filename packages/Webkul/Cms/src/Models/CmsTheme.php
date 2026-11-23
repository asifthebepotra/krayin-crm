<?php

namespace Webkul\Cms\Models;

use Illuminate\Database\Eloquent\Model;

class CmsTheme extends Model
{
    protected $table = 'cms_themes';

    protected $fillable = [
        'slug',
        'name',
        'version',
        'author',
        'description',
        'manifest_json',
        'view_path',
        'asset_path',
        'checksum',
        'installed_at',
    ];

    protected $casts = [
        'manifest_json' => 'array',
        'installed_at' => 'datetime',
    ];
}
