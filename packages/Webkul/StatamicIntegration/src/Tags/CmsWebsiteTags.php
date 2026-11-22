<?php

namespace Webkul\StatamicIntegration\Tags;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Statamic\Tags\Tags;

class CmsWebsiteTags extends Tags
{
    public static function handle(): string
    {
        return 'cms_website';
    }

    public function site_name(): string
    {
        return (string) $this->setting('site_name', config('app.name', 'Website'));
    }

    public function seo_title(): string
    {
        return (string) $this->setting('seo_title', '');
    }

    public function seo_description(): string
    {
        return (string) $this->setting('seo_description', '');
    }

    protected function setting(string $key, mixed $fallback = null): mixed
    {
        if (! Schema::hasTable('cms_website_settings')) {
            return $fallback;
        }

        return DB::table('cms_website_settings')
            ->where('id', 1)
            ->value($key) ?: $fallback;
    }
}