<?php

namespace Webkul\StatamicIntegration\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Statamic\Statamic;

class InstallStarterContent extends Command
{
    protected $signature = 'statamic-integration:install-starter-content';

    protected $description = 'Install starter Pages and Blog content and grant CMS access to Krayin administrators';

    public function handle(): int
    {
        if (! class_exists(Statamic::class)) {
            $this->components->error('Statamic CMS is not installed.');

            return self::FAILURE;
        }

        $source = __DIR__.'/../../Resources/Statamic';

        if (! File::isDirectory($source)) {
            $this->components->error('The Statamic starter content directory is missing.');

            return self::FAILURE;
        }

        $copied = 0;
        $skipped = 0;

        foreach (File::allFiles($source) as $file) {
            $relativePath = $file->getRelativePathname();
            $destination = base_path($relativePath);

            if (File::exists($destination)) {
                $skipped++;

                continue;
            }

            File::ensureDirectoryExists(dirname($destination));

            if (! File::copy($file->getPathname(), $destination)) {
                $this->components->error("Unable to copy [{$relativePath}].");

                return self::FAILURE;
            }

            $copied++;
        }

        $this->components->info("Installed {$copied} starter files.");

        if ($skipped > 0) {
            $this->components->warn("Skipped {$skipped} existing files. Your current content was preserved.");
        }

        $this->assignCmsAdministratorRole();

        $this->components->info('Open the Statamic Control Panel and review the Pages and Blog collections.');

        return self::SUCCESS;
    }

    /**
     * Assign the starter CMS administrator role to active Krayin users
     * whose Krayin role is already configured as a full administrator.
     *
     * This changes only the Statamic pivot. Krayin role_id and CRM
     * permissions are never modified.
     */
    protected function assignCmsAdministratorRole(): void
    {
        $roleFile = resource_path('users/roles.yaml');

        if (
            ! File::exists($roleFile)
            || ! str_contains(File::get($roleFile), 'cms-administrator:')
        ) {
            $this->components->warn(
                'The cms-administrator Statamic role was not found. Existing role files were preserved; assign a CMS role manually.'
            );

            return;
        }

        $pivotTable = config('statamic.users.tables.role_user', 'statamic_role_user');

        if (! Schema::hasTable($pivotTable)) {
            $this->components->warn(
                'Statamic user tables are not ready. Run database migrations, then rerun this command to grant CMS access.'
            );

            return;
        }

        $adminIds = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.status', 1)
            ->where('roles.permission_type', 'all')
            ->pluck('users.id');

        foreach ($adminIds as $userId) {
            DB::table($pivotTable)->updateOrInsert(
                [
                    'user_id' => $userId,
                    'role_id' => 'cms-administrator',
                ],
                []
            );
        }

        $this->components->info(
            "Granted the CMS Administrator role to {$adminIds->count()} active Krayin administrator account(s)."
        );
    }
}