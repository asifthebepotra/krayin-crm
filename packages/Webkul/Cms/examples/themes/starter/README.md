# Krayin Starter Theme

This is the source example for a theme ZIP supported by the Krayin CMS module.

## Build the ZIP

Create a ZIP from the **contents** of this `starter` directory, not from its parent directory.

The ZIP root must contain:

```text
theme.json
README.md
views/
  layout.antlers.html
  default.antlers.html
assets/
  css/
    app.css
```

The package manager rejects PHP, Composer manifests, route files, service providers, symlinks, hidden files, and any file outside the supported Antlers view and static asset folders.

## Asset usage

Use the registered Statamic tag:

```antlers
{{ cms_theme:asset path="assets/css/app.css" }}
```

The tag returns the URL for the active theme's versioned public asset directory.

## Required templates

Every theme must contain:

- `views/layout.antlers.html`
- `views/default.antlers.html`

Additional templates and partials can be placed under `views/` using Statamic's normal Antlers conventions.

## Versioning

Use semantic versions, for example `1.0.0`, `1.1.0`, or `2.0.0`. Install a new version alongside the old one, activate it, and keep the previous version installed until the new theme has been verified.
