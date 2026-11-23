<?php

namespace Webkul\Cms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Cms\Models\CmsUser;
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
            'employees' => CmsUser::query()
                ->where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
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
            'default_lead_owner_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (! empty($validated['primary_domain'])) {
            $validated['primary_domain'] = strtolower($validated['primary_domain']);
        }

        if (! empty($validated['default_lead_owner_id'])) {
            $activeOwner = CmsUser::query()
                ->whereKey($validated['default_lead_owner_id'])
                ->where('status', 1)
                ->exists();

            if (! $activeOwner) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'default_lead_owner_id' => trans('cms::app.settings.active_owner_required'),
                    ]);
            }
        }

        $this->settingsManager->update($validated);

        session()->flash('success', trans('cms::app.settings.updated'));

        return redirect()->route('admin.cms.settings.edit');
    }
}
