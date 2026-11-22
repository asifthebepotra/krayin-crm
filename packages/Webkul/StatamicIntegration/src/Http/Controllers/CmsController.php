<?php

namespace Webkul\StatamicIntegration\Http\Controllers;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class CmsController extends Controller
{
    /**
     * Display the Krayin CMS integration dashboard.
     */
    public function index()
    {
        $statamicInstalled = class_exists(\Statamic\Statamic::class);

        $controlPanelEnabled = $statamicInstalled
            && (bool) config('statamic.cp.enabled', true);

        $controlPanelPath = trim(
            (string) config(
                'statamic.cp.route',
                config('statamic_integration.control_panel_path', 'cp')
            ),
            '/'
        );

        $controlPanelUrl = $controlPanelEnabled
            ? url($controlPanelPath)
            : null;

        $contentApiEnabled = $statamicInstalled
            && (bool) config('statamic.api.enabled', false);

        return view('statamic_integration::index', [
            'statamicInstalled'   => $statamicInstalled,
            'controlPanelEnabled' => $controlPanelEnabled,
            'controlPanelUrl'     => $controlPanelUrl,
            'contentApiEnabled'   => $contentApiEnabled,
            'minimumVersion'      => config('statamic_integration.minimum_statamic_version'),
        ]);
    }

    /**
     * Open the native Statamic Control Panel.
     */
    public function open()
    {
        if (
            ! class_exists(\Statamic\Statamic::class)
            || ! config('statamic.cp.enabled', true)
        ) {
            throw new ServiceUnavailableHttpException(
                null,
                __('statamic_integration::app.messages.statamic_unavailable')
            );
        }

        $controlPanelPath = trim(
            (string) config(
                'statamic.cp.route',
                config('statamic_integration.control_panel_path', 'cp')
            ),
            '/'
        );

        return redirect()->to(url($controlPanelPath));
    }
}