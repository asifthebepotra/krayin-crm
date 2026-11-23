<?php

namespace Webkul\Cms\Services;

use Statamic\Facades\GlobalSet;
use Statamic\Facades\Site;
use Webkul\Cms\Models\CmsSetting;

class CmsSettingsManager
{
    /**
     * Return the single-company website settings record.
     */
    public function current(): CmsSetting
    {
        $settings = CmsSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'site_name'      => config('app.name'),
                'default_locale' => config('app.locale', 'en'),
                'timezone'       => config('app.timezone', 'UTC'),
            ]
        );

        $this->syncSiteGlobal($settings);

        return $settings;
    }

    /**
     * Update the website-level integration settings.
     */
    public function update(array $attributes): CmsSetting
    {
        $settings = $this->current();

        $settings->fill($attributes);

        $settings->save();

        $settings->refresh();

        $this->syncSiteGlobal($settings);

        return $settings;
    }

    /**
     * Keep the Statamic "site" global aligned with the Krayin website name.
     */
    protected function syncSiteGlobal(CmsSetting $settings): void
    {
        if (! class_exists(\Statamic\Statamic::class)) {
            return;
        }

        $set = GlobalSet::find('site');

        if (! $set) {
            $set = GlobalSet::make()
                ->handle('site')
                ->title('Site');

            $set->save();
        }

        $variables = $set->in(Site::default()->handle());
        $siteName = $settings->site_name ?: config('app.name');

        if ($variables->get('site_name') !== $siteName) {
            $variables->set('site_name', $siteName);
            $variables->save();
        }
    }
}
