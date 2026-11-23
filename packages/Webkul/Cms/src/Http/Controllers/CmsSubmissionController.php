<?php

namespace Webkul\Cms\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class CmsSubmissionController extends Controller
{
    public function index(): View
    {
        abort_unless(Schema::hasTable('cms_submission_logs'), 503, trans('cms::app.submissions.install_required'));

        $submissions = DB::table('cms_submission_logs')
            ->orderByDesc('created_at')
            ->paginate(25);

        $summary = [
            'total' => DB::table('cms_submission_logs')->count(),
            'completed' => DB::table('cms_submission_logs')->where('status', 'completed')->count(),
            'failed' => DB::table('cms_submission_logs')->where('status', 'failed')->count(),
            'pending' => DB::table('cms_submission_logs')->whereIn('status', ['pending', 'processing'])->count(),
        ];

        return view('cms::submissions', compact('submissions', 'summary'));
    }
}
