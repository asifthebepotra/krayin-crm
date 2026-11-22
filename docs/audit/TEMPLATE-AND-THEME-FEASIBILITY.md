# SA-01 Feasibility: Statamic Template and ZIP Theme Architecture

**Selected releases:** Krayin CRM v2.2.8 and Statamic CMS v6.42.0  
**Status:** Source-supported design candidate; runtime prototype required  
**Scope:** One company, one public website, one active theme, native Statamic editing.

## 1. Statamic's renderer is already the CMS engine

The pinned Statamic v6.42.0 source shows this flow:

1. Statamic's `FrontendController` resolves content for the request.
2. It creates a `Statamic\\View\\View` with the selected template and configured layout.
3. `Statamic\\View\\View` resolves the template through Laravel's view factory, first trying `templates.<handle>` and then the supplied view handle.
4. Layouts are resolved similarly, first trying `layouts.<handle>`.
5. The Statamic Antlers engine parses the selected view. Partials and includes are also resolved through Laravel's view factory.

Source references:
- [FrontendController](https://github.com/statamic/cms/blob/v6.42.0/src/Http/Controllers/FrontendController.php)
- [Statamic View](https://github.com/statamic/cms/blob/v6.42.0/src/View/View.php)
- [Antlers Engine](https://github.com/statamic/cms/blob/v6.42.0/src/View/Antlers/Engine.php)
- [Partial tag](https://github.com/statamic/cms/blob/v6.42.0/src/Tags/Partial.php)
- [Include tag](https://github.com/statamic/cms/blob/v6.42.0/src/Tags/IncludeTag.php)
- [ViewServiceProvider](https://github.com/statamic/cms/blob/v6.42.0/src/Providers/ViewServiceProvider.php)

The integration should preserve this renderer. Do not replace Statamic's FrontendController, View class or Antlers engine.

## 2. What Statamic's existing Theme functionality does not provide

The pinned Statamic v6.42.0 `Statamic\\Tags\\Theme` class is an asset-path helper and is marked deprecated for removal in v7. It does not implement a complete theme package system, active site theme registry, ZIP installation or safe template switching.

The CP ThemeController in this release returns marketplace theme information and clears its cache; it is not a website template/ZIP installer.

Therefore the following are **custom integration features**:
- ZIP package validation and installation.
- Installed theme registry and version metadata.
- One active theme for the public website.
- Preview and activation workflow.
- Theme-specific asset URL resolution.
- Template/layout/partial resolution for the active theme.

Use native Statamic content, collection, blueprint, asset, navigation, form and publishing features. Only the theme package selection and file lifecycle are custom.

## 3. Theme package contract for the first release

The first theme package should contain only templates and static assets. Keep the content schema (collections, blueprints, taxonomies and form definitions) application-owned rather than letting theme ZIPs silently change the CMS content model.

Suggested package:

```text
corporate-theme.zip
└── corporate-theme/
    ├── theme.json
    ├── preview.png
    ├── README.md
    ├── templates/
    │   ├── home.antlers.html
    │   ├── default.antlers.html
    │   └── contact.antlers.html
    ├── layouts/
    │   └── layout.antlers.html
    ├── partials/
    │   ├── header.antlers.html
    │   ├── footer.antlers.html
    │   └── components/
    └── assets/
        ├── css/
        ├── js/
        └── images/
```

Example manifest:

```json
{
  "name": "Corporate Theme",
  "slug": "corporate",
  "version": "1.0.0",
  "minimum_statamic_version": "6.42.0",
  "template_language": "antlers",
  "entrypoints": {
    "css": ["assets/css/app.css"],
    "js": ["assets/js/app.js"]
  }
}
```

Manifest values are illustrative; final schema must be versioned and validated.

## 4. Active theme resolution candidate

A practical candidate is a custom Laravel view-finder adapter/decorator scoped to public Statamic frontend rendering:

- Resolve template handles from the active theme's `templates/` directory.
- Resolve layout handles from the active theme's `layouts/` directory.
- Resolve partial/include handles from the active theme's `partials/` directory.
- Fall back to application defaults only where explicitly allowed.
- Leave Krayin admin and Statamic Control Panel views unaffected.
- Preserve Statamic's Antlers engine and cascade/context.
- Ensure the active theme context is request-scoped and reset after the response.

The exact extension point must be verified in the runtime prototype. Laravel's view finder is global application infrastructure; do not mutate a singleton's search paths per request without reliable restoration. Long-lived PHP workers make a request-aware resolver and concurrency tests important.

Alternative fallback: for the MVP, keep the active theme's template files in Statamic's ordinary view search path and use a controlled deployment copy/symlink switch. This is simpler but the switch/rollback must be atomic and must not affect CP views.

## 5. Theme assets

Do not depend on the deprecated Statamic Theme tag for theme selection.

Recommended asset contract:
- Package assets are extracted under a controlled public path such as `public/themes/<slug>/<version>/`.
- The active theme manifest identifies prebuilt CSS/JS entrypoints.
- A small integration-owned Antlers tag/helper generates asset URLs for the current active theme.
- Use hashed/prebuilt assets; no npm install or build script is executed from an uploaded ZIP.
- Keep versioned theme directories so activation can be rolled back without overwriting a currently active package.

A custom helper/tag should use the active theme registry and should not accept arbitrary filesystem paths from content or request parameters.

## 6. ZIP installation security

Treat every uploaded theme ZIP as untrusted:

- Enforce compressed and extracted-size limits.
- Validate a single root package directory and a strict manifest schema.
- Reject absolute paths, `..` path traversal, NUL bytes, symlinks and duplicate normalized paths.
- Reject PHP files, executable scripts, Composer manifests/autoloaders and service-provider/route files.
- Extract into a temporary staging directory outside the public web root.
- Validate required directories, file extensions, template language and manifest compatibility.
- Move into a versioned installed directory only after validation.
- Store installation metadata and checksum.
- Preview in a restricted frontend context; no privileged CP credentials should be exposed to theme previews.
- Activate transactionally, clear only relevant caches and preserve the previous active version.
- Support rollback.
- Delete only inactive packages whose paths are inside the controlled theme root.

## 7. Content-schema compatibility

Themes should declare supported section/component handles in `theme.json`, but should not silently replace or mutate application-owned blueprints.

Example:
- Application-owned page blueprint includes a replicator field with supported section sets.
- Theme declares which set handles it has render partials for.
- The public renderer displays supported sets and logs or safely handles unsupported sets.
- A theme preview should show representative dummy content without changing production entries.

This keeps page content portable when the active theme changes.

## 8. Theme acceptance tests

- Valid ZIP installs to staging directory.
- Invalid manifest is rejected.
- ZIP path traversal and symlink archives are rejected.
- PHP or executable files are rejected.
- A page can render a theme-specific Antlers template.
- Theme-specific layouts and nested partials resolve correctly.
- Statamic CP and Krayin admin views remain unchanged.
- CSS/JS assets resolve only from the installed active theme.
- Preview does not activate the theme.
- Activation changes the public template output without changing content entries.
- Rollback restores the previous output.
- Cache invalidation is correct after activation and publishing.
- Two simultaneous frontend requests cannot leak active theme state.
- Inactive theme deletion cannot remove active or shared content/assets.

## 9. Runtime decision

The ZIP theme manager is not a reason to block Statamic installation. First prove native Statamic rendering inside Krayin. Then prototype the view-finder strategy on a disposable test theme. If request-scoped resolution cannot be implemented safely with supported Laravel/Statamic extension points, use a simpler atomic theme-directory switch for the first release rather than patching Statamic core.