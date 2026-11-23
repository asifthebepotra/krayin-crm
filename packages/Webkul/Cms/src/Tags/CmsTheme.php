<?php

namespace Webkul\Cms\Tags;

use RuntimeException;
use Statamic\Tags\Tags;
use Webkul\Cms\Services\CmsThemeManager;

class CmsTheme extends Tags
{
    protected static $handle = 'cms_theme';

    public function asset(): string
    {
        $path = (string) $this->params->get('path');

        if ($path === '') {
            throw new RuntimeException('The cms_theme:asset tag requires a path parameter.');
        }

        return app(CmsThemeManager::class)->assetUrl($path);
    }
}
