<?php

namespace Webkul\Cms\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Cms\Services\CmsSettingsManager;

class CmsController extends Controller
{
    public function __construct(
        protected CmsSettingsManager $settingsManager
    ) {}

    /**
     * Show the Krayin entry point for the native Statamic Control Panel.
     */
    public function index(): View
    {
        $statamicInstalled = class_exists(\Statamic\Statamic::class);

        $controlPanelEnabled = $statamicInstalled
            && (bool) config('statamic.cp.enabled', true);

        $controlPanelPath = trim(
            (string) config('statamic.cp.route', config('cms.control_panel_path', 'cp')),
            '/'
        );

        $controlPanelUrl = url($controlPanelPath);

        $statamicVersion = null;

        if (
            $statamicInstalled
            && class_exists(\Composer\InstalledVersions::class)
            && \Composer\InstalledVersions::isInstalled('statamic/cms')
        ) {
            $statamicVersion = \Composer\InstalledVersions::getPrettyVersion('statamic/cms');
        }

        return view('cms::index', [
            'settings'            => $this->settingsManager->current(),
            'statamicInstalled'   => $statamicInstalled,
            'controlPanelEnabled' => $controlPanelEnabled,
            'controlPanelUrl'     => $controlPanelUrl,
            'statamicVersion'     => $statamicVersion,
        ]);
    }
}
