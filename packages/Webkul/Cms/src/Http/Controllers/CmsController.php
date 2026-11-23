<?php

namespace Webkul\Cms\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $submissionSummary = [
            'total' => 0,
            'completed' => 0,
            'failed' => 0,
        ];

        if (Schema::hasTable('cms_submission_logs')) {
            $submissionSummary = [
                'total' => DB::table('cms_submission_logs')->count(),
                'completed' => DB::table('cms_submission_logs')->where('status', 'completed')->count(),
                'failed' => DB::table('cms_submission_logs')->where('status', 'failed')->count(),
            ];
        }

        return view('cms::index', [
            'settings'            => Schema::hasTable('cms_settings')
                ? $this->settingsManager->current()
                : new CmsSetting(['site_name' => config('app.name')]),
            'statamicInstalled'   => $statamicInstalled,
            'controlPanelEnabled' => $controlPanelEnabled,
            'controlPanelUrl'     => $controlPanelUrl,
            'statamicVersion'     => $statamicVersion,
            'submissionSummary'   => $submissionSummary,
        ]);
    }
}
