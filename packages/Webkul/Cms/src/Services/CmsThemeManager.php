<?php

namespace Webkul\Cms\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Statamic\Statamic;
use Webkul\Cms\Models\CmsSetting;
use Webkul\Cms\Models\CmsTheme;
use ZipArchive;

class CmsThemeManager
{
    private const MAX_ARCHIVE_BYTES = 25 * 1024 * 1024;

    private const MAX_UNCOMPRESSED_BYTES = 100 * 1024 * 1024;

    private const MAX_ARCHIVE_ENTRIES = 500;

    private const ALLOWED_ASSET_EXTENSIONS = [
        'css', 'js', 'png', 'jpg', 'jpeg', 'webp', 'gif',
        'avif', 'ico', 'woff', 'woff2', 'ttf', 'otf',
    ];

    public function all()
    {
        if (! Schema::hasTable('cms_themes')) {
            return collect();
        }

        return CmsTheme::query()
            ->orderBy('name')
            ->orderByDesc('installed_at')
            ->get();
    }

    public function active(): ?CmsTheme
    {
        if (! Schema::hasTable('cms_settings') || ! Schema::hasTable('cms_themes')) {
            return null;
        }

        $settings = CmsSetting::query()->first();

        if (! $settings?->active_theme_slug || ! $settings?->active_theme_version) {
            return null;
        }

        $theme = CmsTheme::query()
            ->where('slug', $settings->active_theme_slug)
            ->where('version', $settings->active_theme_version)
            ->first();

        if (! $theme) {
            return null;
        }

        $viewsRoot = realpath(resource_path('views/cms-themes'));
        $assetsRoot = realpath(public_path('themes'));
        $viewsPath = realpath($theme->view_path);
        $assetsPath = realpath($theme->asset_path);

        if (
            ! $viewsRoot
            || ! $assetsRoot
            || ! $viewsPath
            || ! $assetsPath
            || ! str_starts_with($viewsPath, $viewsRoot.DIRECTORY_SEPARATOR)
            || ! str_starts_with($assetsPath, $assetsRoot.DIRECTORY_SEPARATOR)
        ) {
            return null;
        }

        return $theme;
    }

    /**
     * Validate and install a ZIP containing Antlers views and static assets.
     */
    public function install(UploadedFile $archive): CmsTheme
    {
        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages([
                'theme_zip' => 'The PHP ZIP extension is required to install themes.',
            ]);
        }

        if ($archive->getSize() > self::MAX_ARCHIVE_BYTES) {
            throw ValidationException::withMessages([
                'theme_zip' => 'The theme archive exceeds the 25 MB upload limit.',
            ]);
        }

        $zip = new ZipArchive;
        $opened = $zip->open($archive->getRealPath(), ZipArchive::RDONLY);

        if ($opened !== true) {
            throw ValidationException::withMessages([
                'theme_zip' => 'The uploaded file is not a readable ZIP archive.',
            ]);
        }

        $stagingRoot = storage_path('app/cms-theme-staging/'.Str::uuid());
        $stagingViews = $stagingRoot.'/views';
        $stagingAssets = $stagingRoot.'/assets';
        $viewDestination = null;
        $assetDestination = null;

        try {
            if ($zip->numFiles > self::MAX_ARCHIVE_ENTRIES) {
                throw new RuntimeException('The theme archive contains too many files.');
            }

            $manifestContent = $zip->getFromName('theme.json');

            if (! is_string($manifestContent)) {
                throw new RuntimeException('The archive must contain a root theme.json manifest.');
            }

            $manifest = json_decode($manifestContent, true, 512, JSON_THROW_ON_ERROR);

            $this->validateManifest($manifest);

            $existing = CmsTheme::query()
                ->where('slug', $manifest['slug'])
                ->where('version', $manifest['version'])
                ->exists();

            if ($existing) {
                throw new RuntimeException('This theme version is already installed.');
            }

            $records = [];
            $seenPaths = [];
            $totalBytes = 0;
            $hasLayout = false;
            $hasDefaultTemplate = false;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);

                if (! $stat) {
                    throw new RuntimeException('The archive contains an unreadable entry.');
                }

                $rawName = (string) $stat['name'];
                $isDirectory = $this->assertSafeEntryType($zip, $index);

                if ($isDirectory || str_ends_with($rawName, '/')) {
                    continue;
                }

                $name = $this->normalizeArchivePath($rawName);

                $size = (int) ($stat['size'] ?? 0);
                $totalBytes += $size;

                if ($totalBytes > self::MAX_UNCOMPRESSED_BYTES) {
                    throw new RuntimeException('The uncompressed theme exceeds the 100 MB limit.');
                }

                $this->validateArchiveFile($name);

                $pathKey = strtolower($name);

                if (isset($seenPaths[$pathKey])) {
                    throw new RuntimeException('The archive contains duplicate or case-colliding file paths.');
                }

                $seenPaths[$pathKey] = true;

                $contents = $zip->getFromIndex($index);

                if (! is_string($contents) || strlen($contents) !== $size) {
                    throw new RuntimeException("Unable to read archive file: {$name}");
                }

                if (str_starts_with($name, 'views/')) {
                    $viewRelative = substr($name, strlen('views/'));

                    if ($viewRelative === 'layout.antlers.html') {
                        $hasLayout = true;
                    }

                    if ($viewRelative === 'default.antlers.html') {
                        $hasDefaultTemplate = true;
                    }
                }

                $records[] = [
                    'name' => $name,
                    'contents' => $contents,
                ];
            }

            if (! $hasLayout || ! $hasDefaultTemplate) {
                throw new RuntimeException('A theme must include views/layout.antlers.html and views/default.antlers.html.');
            }

            $slug = $manifest['slug'];
            $version = $manifest['version'];

            $viewDestination = resource_path("views/cms-themes/{$slug}/{$version}");
            $assetDestination = public_path("themes/{$slug}/{$version}");

            if (File::exists($viewDestination) || File::exists($assetDestination)) {
                throw new RuntimeException('A theme directory with this slug and version already exists.');
            }

            File::ensureDirectoryExists($stagingViews);
            File::ensureDirectoryExists($stagingAssets);

            foreach ($records as $record) {
                $name = $record['name'];
                $contents = $record['contents'];

                if (str_starts_with($name, 'views/')) {
                    $relative = substr($name, strlen('views/'));
                    $destination = $stagingViews.'/'.$relative;
                } elseif (str_starts_with($name, 'assets/')) {
                    $relative = substr($name, strlen('assets/'));
                    $destination = $stagingAssets.'/assets/'.$relative;
                } elseif (str_starts_with($name, 'preview.')) {
                    $destination = $stagingAssets.'/'.$name;
                } else {
                    continue;
                }

                File::ensureDirectoryExists(dirname($destination));

                if (file_put_contents($destination, $contents) === false) {
                    throw new RuntimeException("Unable to write theme file: {$name}");
                }
            }

            $this->assertNonEmptyDirectory($stagingViews, 'views');

            File::ensureDirectoryExists(dirname($viewDestination));
            File::ensureDirectoryExists(dirname($assetDestination));

            if (! File::moveDirectory($stagingViews, $viewDestination)) {
                throw new RuntimeException('Unable to install theme views.');
            }

            if (File::exists($stagingAssets) && ! File::moveDirectory($stagingAssets, $assetDestination)) {
                throw new RuntimeException('Unable to install theme assets.');
            }

            return CmsTheme::query()->create([
                'slug' => $slug,
                'name' => $manifest['name'],
                'version' => $version,
                'author' => $manifest['author'] ?? null,
                'description' => $manifest['description'] ?? null,
                'manifest_json' => $manifest,
                'view_path' => $viewDestination,
                'asset_path' => $assetDestination,
                'checksum' => hash_file('sha256', $archive->getRealPath()),
                'installed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            if ($viewDestination && File::exists($viewDestination)) {
                File::deleteDirectory($viewDestination);
            }

            if ($assetDestination && File::exists($assetDestination)) {
                File::deleteDirectory($assetDestination);
            }

            if ($exception instanceof ValidationException) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'theme_zip' => $exception->getMessage(),
            ]);
        } finally {
            $zip->close();
            File::deleteDirectory($stagingRoot);
        }
    }

    public function activate(string $slug, string $version): CmsTheme
    {
        $theme = CmsTheme::query()
            ->where('slug', $slug)
            ->where('version', $version)
            ->firstOrFail();

        $viewsRoot = realpath(resource_path('views/cms-themes'));
        $viewsPath = realpath($theme->view_path);

        if (
            ! $viewsRoot
            || ! $viewsPath
            || ! str_starts_with($viewsPath, $viewsRoot.DIRECTORY_SEPARATOR)
            || ! File::isDirectory($viewsPath)
        ) {
            throw new RuntimeException('The installed theme view directory is missing or invalid.');
        }

        $settings = CmsSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'site_name' => config('app.name'),
                'default_locale' => config('app.locale', 'en'),
                'timezone' => config('app.timezone', 'UTC'),
            ]
        );

        $settings->forceFill([
            'active_theme_slug' => $theme->slug,
            'active_theme_version' => $theme->version,
        ])->save();

        app('view')->getFinder()->flush();

        return $theme;
    }

    public function remove(string $slug, string $version): void
    {
        $theme = CmsTheme::query()
            ->where('slug', $slug)
            ->where('version', $version)
            ->firstOrFail();

        $settings = CmsSetting::query()->first();

        if (
            $settings
            && $settings->active_theme_slug === $theme->slug
            && $settings->active_theme_version === $theme->version
        ) {
            throw new RuntimeException('Deactivate the theme before removing it.');
        }

        $viewsRoot = realpath(resource_path('views/cms-themes'));
        $assetsRoot = realpath(public_path('themes'));
        $viewsPath = realpath($theme->view_path);
        $assetsPath = realpath($theme->asset_path);

        if ($viewsRoot && $viewsPath && str_starts_with($viewsPath, $viewsRoot.DIRECTORY_SEPARATOR)) {
            File::deleteDirectory($viewsPath);
        }

        if ($assetsRoot && $assetsPath && str_starts_with($assetsPath, $assetsRoot.DIRECTORY_SEPARATOR)) {
            File::deleteDirectory($assetsPath);
        }

        $theme->delete();
    }

    public function assetUrl(string $relativePath): string
    {
        $theme = $this->active();

        if (! $theme) {
            return '';
        }

        $relativePath = $this->normalizeArchivePath($relativePath);

        if (! str_starts_with($relativePath, 'assets/')) {
            throw new RuntimeException('Theme assets must be requested from the assets/ directory.');
        }

        $assetRoot = realpath($theme->asset_path);
        $path = $assetRoot ? realpath($assetRoot.'/'.$relativePath) : false;

        if (
            ! $assetRoot
            || ! $path
            || ! str_starts_with($path, $assetRoot.DIRECTORY_SEPARATOR)
            || ! File::isFile($path)
        ) {
            return '';
        }

        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $relativePath)));

        return asset('themes/'.$theme->slug.'/'.$theme->version.'/'.$encodedPath);
    }

    protected function validateManifest(array $manifest): void
    {
        foreach (['name', 'slug', 'version', 'minimum_statamic_version'] as $key) {
            if (! isset($manifest[$key]) || ! is_string($manifest[$key]) || trim($manifest[$key]) === '') {
                throw new RuntimeException("The theme manifest requires a non-empty {$key}.");
            }
        }

        if (! preg_match('/^[a-z0-9][a-z0-9-]{0,78}$/', $manifest['slug'])) {
            throw new RuntimeException('Theme slug must contain lowercase letters, numbers and hyphens only.');
        }

        if (! preg_match('/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.-]+)?$/', $manifest['version'])) {
            throw new RuntimeException('Theme version must use a semantic version such as 1.0.0.');
        }

        $installedVersion = ltrim(Statamic::version(), 'v');

        if (version_compare($installedVersion, ltrim($manifest['minimum_statamic_version'], 'v'), '<')) {
            throw new RuntimeException('This theme requires a newer Statamic version.');
        }

        if (isset($manifest['author']) && ! is_string($manifest['author'])) {
            throw new RuntimeException('Theme author must be a string.');
        }

        if (isset($manifest['description']) && ! is_string($manifest['description'])) {
            throw new RuntimeException('Theme description must be a string.');
        }
    }

    protected function normalizeArchivePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        if (
            $path === ''
            || str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:/', $path)
            || str_contains($path, "\0")
        ) {
            throw new RuntimeException('The theme archive contains an invalid path.');
        }

        $segments = explode('/', $path);

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || str_starts_with($segment, '.')) {
                throw new RuntimeException('The theme archive contains an unsafe path.');
            }
        }

        return implode('/', $segments);
    }

    protected function assertSafeEntryType(ZipArchive $zip, int $index): bool
    {
        $opsys = 0;
        $attributes = 0;

        if (! $zip->getExternalAttributesIndex($index, $opsys, $attributes)) {
            return false;
        }

        $mode = ($attributes >> 16) & 0170000;

        if ($mode === 0120000) {
            throw new RuntimeException('Symbolic links are not allowed in theme archives.');
        }

        if ($mode === 0040000) {
            return true;
        }

        if ($mode !== 0 && $mode !== 0100000) {
            throw new RuntimeException('Special filesystem entries are not allowed in theme archives.');
        }

        return false;
    }

    protected function validateArchiveFile(string $name): void
    {
        if ($name === 'theme.json' || $name === 'README.md' || preg_match('/^preview\.(png|jpg|jpeg|webp)$/i', $name)) {
            return;
        }

        if (str_starts_with($name, 'views/')) {
            if (! str_ends_with($name, '.antlers.html')) {
                throw new RuntimeException('Theme views must use the .antlers.html template extension.');
            }

            return;
        }

        if (str_starts_with($name, 'assets/')) {
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (! in_array($extension, self::ALLOWED_ASSET_EXTENSIONS, true)) {
                throw new RuntimeException("Theme asset type .{$extension} is not allowed.");
            }

            return;
        }

        throw new RuntimeException("The theme archive contains an unsupported file: {$name}");
    }

    protected function assertNonEmptyDirectory(string $path, string $name): void
    {
        if (! File::isDirectory($path) || count(File::allFiles($path)) === 0) {
            throw new RuntimeException("The theme archive does not contain any {$name} files.");
        }
    }
}
