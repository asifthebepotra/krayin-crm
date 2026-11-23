<?php

namespace Webkul\Cms\Services;

use Webkul\Cms\Models\CmsSetting;

class CmsSettingsManager
{
    /**
     * Return the single-company website settings record.
     */
    public function current(): CmsSetting
    {
        return CmsSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'site_name'      => config('app.name'),
                'default_locale' => config('app.locale', 'en'),
                'timezone'       => config('app.timezone', 'UTC'),
            ]
        );
    }

    /**
     * Update the website-level integration settings.
     */
    public function update(array $attributes): CmsSetting
    {
        $settings = $this->current();

        $settings->fill($attributes);

        $settings->save();

        return $settings->refresh();
    }
}
