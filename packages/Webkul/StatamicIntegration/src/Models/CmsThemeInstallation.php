<?php

namespace Webkul\StatamicIntegration\Models;

use Illuminate\Database\Eloquent\Model;

class CmsThemeInstallation extends Model
{
    protected $table = 'cms_theme_installations';

    protected $fillable = [
        'slug',
        'version',
        'name',
        'author',
        'description',
        'manifest_json',
        'install_path',
        'checksum',
        'installed_at',
    ];

    protected $casts = [
        'manifest_json' => 'array',
        'installed_at' => 'datetime',
    ];
}