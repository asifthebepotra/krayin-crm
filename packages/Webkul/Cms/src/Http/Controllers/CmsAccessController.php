<?php

namespace Webkul\Cms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Statamic\Auth\PermissionCache;
use Statamic\Facades\Blink;
use Webkul\Cms\Models\CmsUser;

class CmsAccessController extends Controller
{
    public function index(): View
    {
        $users = CmsUser::query()
            ->select(['id', 'name', 'email', 'status'])
            ->orderBy('name')
            ->paginate(20);

        $assignments = DB::table('statamic_role_user')
            ->whereIn('user_id', $users->getCollection()->pluck('id'))
            ->pluck('role_id', 'user_id');

        return view('cms::users', [
            'users' => $users,
            'assignments' => $assignments,
            'roles' => config('cms.roles', []),
        ]);
    }

    public function update(Request $request, int $userId): RedirectResponse
    {
        $user = CmsUser::query()->findOrFail($userId);

        if (! (bool) $user->status) {
            abort(422, trans('cms::app.users.inactive_user'));
        }

        $validated = $request->validate([
            'role' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('cms.roles', [])))],
        ]);

        DB::transaction(function () use ($user, $validated) {
            DB::table('statamic_role_user')
                ->where('user_id', $user->getKey())
                ->delete();

            if (! empty($validated['role'])) {
                DB::table('statamic_role_user')->insert([
                    'user_id' => $user->getKey(),
                    'role_id' => $validated['role'],
                ]);
            }
        });

        if (class_exists(PermissionCache::class)) {
            app(PermissionCache::class)->clear();
        }

        if (class_exists(Blink::class)) {
            Blink::forget('eloquent-user-find-'.$user->getKey());
        }

        return redirect()
            ->route('admin.cms.users.index')
            ->with('success', trans('cms::app.users.updated'));
    }
}
