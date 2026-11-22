# Statamic Website Theme ZIP Package

This document defines the ZIP format accepted by the Krayin Statamic CMS module.

## 1. Package layout

The ZIP archive must contain the package files at its root. Do not zip an enclosing directory.

```text
corporate-starter.zip
├── theme.json
├── README.md
├── preview.png
├── views/
│   ├── templates/
│   │   ├── home.antlers.html
│   │   ├── default.antlers.html
│   │   └── contact.antlers.html
│   ├── layouts/
│   │   └── layout.antlers.html
│   └── partials/
│       ├── header.antlers.html
│       └── footer.antlers.html
└── assets/
    ├── css/
    │   └── site.css
    ├── js/
    │   └── site.js
    └── images/
        └── logo.svg
```

Only Antlers templates and approved static assets are accepted. Theme packages cannot contain PHP, Blade views, Composer files, service providers, route definitions, shell scripts, or installation scripts.

## 2. Manifest

A minimal `theme.json`:

```json
{
  "name": "Corporate Starter",
  "slug": "corporate-starter",
  "version": "1.0.0",
  "author": "Your Studio",
  "description": "A clean responsive business website theme.",
  "minimum_statamic_version": "6.42.0",
  "template_language": "antlers"
}
```

Required keys:

- `name`
- `slug` — lowercase letters, numbers and hyphens only.
- `version` — semantic version.
- `minimum_statamic_version`

## 3. Template resolution

The selected theme is prepended to Laravel's view finder for Statamic public website routes only.

Statamic retains its normal renderer and content cascade:
- `views/templates/` for collection/page templates.
- `views/layouts/` for layouts.
- `views/partials/` for partials.

Krayin admin pages and Statamic Control Panel views are not rendered through the active public theme.

## 4. Theme assets

Assets are published under:

```text
/public/themes/{slug}/{version}/assets/
```

Use the registered Antlers tag:

```antlers
<link rel="stylesheet" href="{{ cms_theme:asset path='css/site.css' }}">
<script src="{{ cms_theme:asset path='js/site.js' }}" defer></script>
```

The helper resolves only files inside the active theme's published asset directory. It rejects absolute paths and path traversal.

## 5. Upload validation

The theme manager enforces:
- Maximum compressed ZIP size: 15 MB.
- Maximum extracted size: 100 MB.
- Maximum archive entries: 1,000.
- Root `theme.json` manifest.
- Unique theme slug/version pair.
- No absolute paths, dot segments, backslash paths, duplicate paths or symbolic links.
- Only the documented views and static asset directories.
- At least one Antlers template.

The installer extracts to a private staging directory, validates the package, then moves it to a versioned installed directory. Assets are copied to the public theme directory only after validation.

## 6. Preview and activation

- **Preview** creates a signed URL valid for 10 minutes.
- Preview uses the selected theme without changing the active website theme setting.
- The preview request requires an active Krayin user who has Statamic's `access cp` permission.
- **Activate** stores the selected slug/version in `cms_website_settings`.
- **Use built-in theme** clears the active theme selection.
- An active theme cannot be removed.
- Inactive theme removal verifies that the resolved paths remain inside the managed installation/public theme roots.

## 7. Build and ZIP

Build the theme locally. Then select the contents of the theme directory (not the parent directory) and compress those files into a ZIP so `theme.json` is at the archive root.

The ZIP installer does not run build tools. CSS and JavaScript must already be built before packaging.
