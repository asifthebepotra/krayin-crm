<?php

namespace Webkul\StatamicIntegration\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CmsSubmissionController extends Controller
{
    public function index()
    {
        $logs = Schema::hasTable('cms_submission_logs')
            ? DB::table('cms_submission_logs')
                ->orderByDesc('created_at')
                ->paginate(25)
                ->withQueryString()
            : null;

        return view('statamic_integration::submissions.index', [
            'logs' => $logs,
            'tablesReady' => $logs !== null,
        ]);
    }
}