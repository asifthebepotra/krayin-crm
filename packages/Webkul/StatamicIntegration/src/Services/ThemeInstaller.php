<?php

namespace Webkul\StatamicIntegration\Services;

use Composer\InstalledVersions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Statamic\Statamic;
use Webkul\StatamicIntegration\Models\CmsThemeInstallation;
use ZipArchive;

class ThemeInstaller
{
    private const MAX_ARCHIVE_BYTES = 15 * 1024 * 1024;

    private const MAX_EXTRACTED_BYTES = 100 * 1024 * 1024;

    private const MAX_ARCHIVE_FILES = 1000;

    private const VIEW_ROOTS = [
        'views/templates/',
        'views/layouts/',
        'views/partials/',
    ];

    private const ASSET_EXTENSIONS = [
        'css', 'js', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg',
        'ico', 'woff', 'woff2', 'ttf', 'eot',
    ];

    public function install(UploadedFile $archive): CmsThemeInstallation
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required to install themes.');
        }

        if ($archive->getSize() > self::MAX_ARCHIVE_BYTES) {
            throw new RuntimeException('The theme archive exceeds the 15 MB upload limit.');
        }

        $zip = new ZipArchive();

        if ($zip->open($archive->getRealPath()) !== true) {
            throw new RuntimeException('The uploaded file is not a valid ZIP archive.');
        }

        $staging = storage_path('app/statamic-themes/.staging/'.Str::uuid());
        $target = null;
        $publicAssets = null;

        try {
            [$manifest, $entries] = $this->validateArchive($zip);

            $slug = $manifest['slug'];
            $version = $manifest['version'];

            if (CmsThemeInstallation::where('slug', $slug)->where('version', $version)->exists()) {
                throw new RuntimeException("Theme [{$slug}] version [{$version}] is already installed.");
            }

            $target = storage_path("app/statamic-themes/{$slug}/{$version}");

            if (File::exists($target)) {
                throw new RuntimeException('The target theme directory already exists. Remove the orphaned directory before reinstalling.');
            }

            File::ensureDirectoryExists($staging);

            if (! $zip->extractTo($staging, $entries)) {
                throw new RuntimeException('The theme archive could not be safely extracted.');
            }

            $this->validateExtractedFiles($staging);
            $this->sanitizeSvgAssets($staging);

            File::ensureDirectoryExists(dirname($target));

            if (! File::moveDirectory($staging, $target)) {
                throw new RuntimeException('Unable to move the validated theme into the installed themes directory.');
            }

            $publicAssets = public_path("themes/{$slug}/{$version}");

            if (File::isDirectory($target.'/assets')) {
                File::ensureDirectoryExists(dirname($publicAssets));

                if (! File::copyDirectory($target.'/assets', $publicAssets.'/assets')) {
                    throw new RuntimeException('Theme assets could not be copied to the public theme directory.');
                }
            }

            if (File::exists($target.'/preview.png')) {
                File::ensureDirectoryExists($publicAssets);

                File::copy($target.'/preview.png', $publicAssets.'/preview.png');
            }

            $checksum = hash_file('sha256', $archive->getRealPath());

            return CmsThemeInstallation::create([
                'slug' => $slug,
                'version' => $version,
                'name' => $manifest['name'],
                'author' => $manifest['author'] ?? null,
                'description' => $manifest['description'] ?? null,
                'manifest_json' => $manifest,
                'install_path' => "statamic-themes/{$slug}/{$version}",
                'checksum' => $checksum,
                'installed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            if ($target && File::isDirectory($target)) {
                File::deleteDirectory($target);
            }

            if ($publicAssets && File::isDirectory($publicAssets)) {
                File::deleteDirectory($publicAssets);
            }

            throw $exception;
        } finally {
            $zip->close();

            if (File::isDirectory($staging)) {
                File::deleteDirectory($staging);
            }
        }
    }

    /**
     * Validate every archive entry before extraction. Theme packages contain
     * Antlers templates and static assets only; executable PHP and scripts
     * are never accepted.
     */
    protected function validateArchive(ZipArchive $zip): array
    {
        if ($zip->numFiles < 2 || $zip->numFiles > self::MAX_ARCHIVE_FILES) {
            throw new RuntimeException('The theme archive must contain between 2 and 1000 entries.');
        }

        $manifestRaw = $zip->getFromName('theme.json');

        if ($manifestRaw === false) {
            throw new RuntimeException('The theme archive must contain a root theme.json manifest.');
        }

        $manifest = json_decode($manifestRaw, true);

        if (! is_array($manifest) || json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('The theme.json manifest is not valid JSON.');
        }

        foreach (['name', 'slug', 'version', 'minimum_statamic_version'] as $required) {
            if (! is_string($manifest[$required] ?? null) || trim($manifest[$required]) === '') {
                throw new RuntimeException("The theme manifest is missing [{$required}].");
            }
        }

        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $manifest['slug'])) {
            throw new RuntimeException('Theme slugs must use lowercase letters, numbers and single hyphens.');
        }

        if (! preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $manifest['version'])) {
            throw new RuntimeException('The theme version must use semantic version notation.');
        }

        $installedStatamic = InstalledVersions::getPrettyVersion('statamic/cms')
            ?: config('statamic_integration.minimum_statamic_version');

        if (version_compare(ltrim($installedStatamic, 'v'), $manifest['minimum_statamic_version'], '<')) {
            throw new RuntimeException('This theme requires a newer Statamic version.');
        }

        $entries = [];
        $seenPaths = [];
        $totalSize = 0;
        $hasTemplate = false;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if (! is_string($name) || $name === '' || str_contains($name, "\0")) {
                throw new RuntimeException('The theme archive contains an invalid path.');
            }

            if (str_contains($name, chr(92))) {
                throw new RuntimeException('Theme archive paths must use forward slashes.');
            }

            $normalized = $name;
            $trimmed = rtrim($normalized, '/');

            $pathKey = strtolower($normalized);

            if (isset($seenPaths[$pathKey])) {
                throw new RuntimeException("The theme archive contains a duplicate or case-colliding path [{$normalized}].");
            }

            $seenPaths[$pathKey] = true;

            $segments = explode('/', $trimmed);

            if (
                str_starts_with($normalized, '/')
                || preg_match('/^[A-Za-z]:/', $normalized)
                || preg_match('/[<>:"|?*\\x00-\\x1F]/', $normalized)
                || in_array('', $segments, true)
                || in_array('..', $segments, true)
                || in_array('.', $segments, true)
                || preg_match('/[. ]$/', end($segments))
                || preg_match('/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\\.|$)/i', end($segments))
            ) {
                throw new RuntimeException('The theme archive contains an unsafe path.');
            }

            $opsys = 0;
            $attributes = 0;

            if ($zip->getExternalAttributesIndex($index, $opsys, $attributes) && $opsys === ZipArchive::OPSYS_UNIX) {
                $fileType = ($attributes >> 16) & 0170000;

                if ($fileType === 0120000) {
                    throw new RuntimeException('Symbolic links are not allowed in theme archives.');
                }
            }

            $isDirectory = str_ends_with($normalized, '/');

            if (! $isDirectory) {
                $stat = $zip->statIndex($index);
                $totalSize += (int) ($stat['size'] ?? 0);

                if ($totalSize > self::MAX_EXTRACTED_BYTES) {
                    throw new RuntimeException('The extracted theme size exceeds the 100 MB limit.');
                }

                if ($normalized === 'theme.json' || $normalized === 'README.md' || $normalized === 'preview.png') {
                    $entries[] = $name;

                    continue;
                }

                if ($this->isTemplatePath($normalized)) {
                    $hasTemplate = true;
                    $entries[] = $name;

                    continue;
                }

                if ($this->isAssetPath($normalized)) {
                    $entries[] = $name;

                    continue;
                }

                throw new RuntimeException("The file [{$normalized}] is not allowed in a theme package.");
            }

            if (
                $trimmed === 'views'
                || $trimmed === 'assets'
                || str_starts_with($trimmed, 'views/')
                || str_starts_with($trimmed, 'assets/')
            ) {
                $entries[] = $name;

                continue;
            }

            throw new RuntimeException("The directory [{$normalized}] is not allowed in a theme package.");
        }

        if (! $hasTemplate) {
            throw new RuntimeException('The theme must include at least one Antlers template inside views/templates/.');
        }

        return [$manifest, $entries];
    }

    protected function isTemplatePath(string $path): bool
    {
        foreach (self::VIEW_ROOTS as $root) {
            if (str_starts_with($path, $root) && str_ends_with($path, '.antlers.html')) {
                return true;
            }
        }

        return false;
    }

    protected function isAssetPath(string $path): bool
    {
        if (! str_starts_with($path, 'assets/')) {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, self::ASSET_EXTENSIONS, true);
    }

 
    protected function sanitizeSvgAssets(string $directory): void
    {
        $assetsDirectory = $directory.'/assets';

        if (! File::isDirectory($assetsDirectory)) {
            return;
        }

        $sanitizer = new Sanitizer();
        $sanitizer->removeRemoteReferences(true);

        foreach (File::allFiles($assetsDirectory) as $file) {
            if (strtolower($file->getExtension()) !== 'svg') {
                continue;
            }

            $clean = $sanitizer->sanitize(File::get($file->getPathname()));

            if ($clean === false) {
                throw new RuntimeException("SVG asset [{$file->getRelativePathname()}] could not be sanitized.");
            }

            File::put($file->getPathname(), $clean);
        }
    }

    protected function validateExtractedFiles(string $directory): void
    {
        if (! File::isDirectory($directory.'/views/templates')) {
            throw new RuntimeException('The theme does not contain a views/templates directory.');
        }

        $templates = File::allFiles($directory.'/views/templates');

        if (count($templates) === 0) {
            throw new RuntimeException('The theme does not contain any templates.');
        }
    }
}