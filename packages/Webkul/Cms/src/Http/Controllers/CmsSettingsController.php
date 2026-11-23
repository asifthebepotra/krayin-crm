<?php

namespace Webkul\Cms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Webkul\Cms\Services\CmsSettingsManager;

class CmsSettingsController extends Controller
{
    public function __construct(
        protected CmsSettingsManager $settingsManager
    ) {}

    public function edit(): View
    {
        return view('cms::settings', [
            'settings' => $this->settingsManager->current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['nullable', 'string', 'max:150'],
            'primary_domain' => [
                'nullable',
                'string',
                'max:253',
                'regex:/^(?=.{1,253}$)(?!-)[a-zA-Z0-9-]{1,63}(?<!-)(\.(?!-)[a-zA-Z0-9-]{1,63}(?<!-))*$/',
            ],
            'default_locale' => ['required', 'string', 'max:12'],
            'timezone' => ['required', 'timezone'],
        ]);

        if (! empty($validated['primary_domain'])) {
            $validated['primary_domain'] = strtolower($validated['primary_domain']);
        }

        $this->settingsManager->update($validated);

        session()->flash('success', trans('cms::app.settings.updated'));

        return redirect()->route('admin.cms.settings.edit');
    }
}
