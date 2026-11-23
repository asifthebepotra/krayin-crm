<?php

namespace Webkul\Cms\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

class CmsController extends Controller
{
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

        return view('cms::index', compact(
            'statamicInstalled',
            'controlPanelEnabled',
            'controlPanelUrl',
            'statamicVersion'
        ));
    }
}
