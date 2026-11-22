<?php

namespace Webkul\StatamicIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CmsWebsiteSettingsController extends Controller
{
    public function index()
    {
        $ready = Schema::hasTable('cms_website_settings');

        $settings = $ready
            ? DB::table('cms_website_settings')->where('id', 1)->first()
            : null;

        return view('statamic_integration::settings.website', [
            'settings' => $settings,
            'tablesReady' => $ready,
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(Schema::hasTable('cms_website_settings'), 503, 'Run the CMS migrations before saving website settings.');

        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:150'],
            'primary_domain' => [
                'nullable',
                'string',
                'max:253',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\\.)+[a-z]{2,}$/i',
            ],
            'default_locale' => ['required', 'string', 'max:20', 'regex:/^[a-z]{2,3}(?:-[A-Z]{2})?$/'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:320'],
        ]);

        $exists = DB::table('cms_website_settings')->where('id', 1)->exists();

        $values = array_merge($validated, [
            'updated_at' => now(),
        ]);

        if (! $exists) {
            $values['created_at'] = now();
        }

        DB::table('cms_website_settings')->updateOrInsert(['id' => 1], $values);

        return redirect()
            ->route('admin.cms.settings.index')
            ->with('success', 'Website settings were saved.');
    }
}