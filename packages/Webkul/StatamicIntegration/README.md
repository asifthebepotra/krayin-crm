# Krayin Statamic CMS Integration

This module adds Statamic CMS to Krayin CRM as a native Krayin package. Statamic remains the content engine and editorial Control Panel; the module connects it to Krayin's employee identity, lead intake and admin workspace.

## Responsibilities

- Register the CMS section in Krayin navigation and ACL.
- Provide a CMS overview, Website Settings and Theme Management screens in Krayin.
- Open Statamic's native Control Panel from Krayin.
- Reuse the Krayin employee session for Statamic authentication.
- Keep CMS roles separate from CRM roles.
- Serve the public website at the domain root through Statamic.
- Install starter Pages, Blog, Contact and Website Media structures.
- Send selected Statamic form submissions into Krayin's existing lead creation workflow.
- Install, preview, activate, deactivate and remove ZIP themes.
- Keep all custom functionality inside this package.

Statamic owns collections, entries, blueprints, assets, forms, publishing and Antlers rendering. Krayin owns employee accounts, CRM roles, leads, contacts, organizations and sales workflows.

## Dependencies

- PHP 8.3+
- Krayin CRM 2.2 / Laravel 12
- Statamic CMS 6.42.0
- Inertia Laravel 2.x

## Krayin routes

The module respects the configurable `APP_ADMIN_PATH`.

- `GET {APP_ADMIN_PATH}/cms` — CMS overview.
- `GET {APP_ADMIN_PATH}/cms/open` — opens the native Statamic Control Panel.
- `GET {APP_ADMIN_PATH}/cms/settings` — website identity and SEO defaults.
- `GET {APP_ADMIN_PATH}/cms/themes` — install and manage website themes.

## First installation

The root Krayin `composer.json` pins Statamic and Inertia 2 and includes this package's PSR-4 namespace.

For a fresh checkout without `vendor/`, run the first dependency update without Composer scripts:

```bash
composer update statamic/cms inertiajs/inertia-laravel --with-all-dependencies --no-scripts
php artisan package:discover --ansi
php artisan statamic:install --ansi
```

Then run the Krayin and integration migrations:

```bash
php artisan migrate
```

Do not run `composer install` before the lockfile includes Statamic and Inertia. Once the initial update completes, the lockfile is current and normal Composer operations can be used.

## Install the starter website

Run:

```bash
php artisan statamic-integration:install-starter-content
```

The command copies files only when the destination does not already exist. Existing Statamic collections, entries, forms, blueprints, roles and views are preserved.

Starter content includes:

- Pages collection with Home, Blog and Contact pages.
- Dated Blog collection.
- Categories and Tags taxonomies.
- Website Media asset container using a separate `cms-assets` disk.
- Page, Blog Post, taxonomy, asset and Contact form blueprints.
- Contact form with native Statamic submission storage and validation.
- Default, Home, Blog listing, Blog detail and Contact Antlers templates.
- Responsive default website layout.

The installer assigns the Statamic `CMS Administrator` role to active Krayin users whose Krayin role has `permission_type = all`. It only inserts Statamic role-pivot rows and never changes Krayin `role_id` or CRM permissions.

## Shared identity and permissions

The integration configures Statamic's Eloquent user repository to use Krayin's existing `user` session guard and a compatible `CmsUser` subclass of `Webkul\\User\\Models\\User`.

- Krayin remains the source of employee accounts, passwords and active status.
- Statamic controls CMS roles and content permissions.
- CMS role/group pivots use `statamic_role_user` and `statamic_group_user`.
- Krayin's `roles`, `groups` and `user_groups` are not reused.
- Statamic CP requests check the Krayin user `status`.
- Statamic's native user creation and password management are not granted to the starter CMS Administrator role.
- Statamic two-factor authentication is disabled until its required user fields are deliberately implemented.

The identity design still needs runtime verification after the package installation is run locally.

## Website Settings

The Krayin Website Settings screen stores:

- Site name.
- Primary domain.
- Default locale.
- Default SEO title.
- Default SEO description.

The Antlers tag `cms_website` exposes the site name and default SEO values to public templates:

```antlers
{{ cms_website:site_name }}
{{ cms_website:seo_title }}
{{ cms_website:seo_description }}
```

Page/article-specific SEO fields take precedence over website defaults.

## Contact form to CRM lead integration

The starter Contact form is enabled in `src/Config/cms.php` with a configurable field map.

After Statamic stores a submission:

1. The integration listener reads the configured field mapping.
2. A unique queue job processes the submission.
3. It finds a person by exact email or creates one through Krayin's `PersonRepository`.
4. It resolves the owner, pipeline, stage, source and lead type.
5. It creates the lead through Krayin's `LeadRepository`.
6. It records the CRM lead ID and result in `cms_submission_logs`.

The integration does not call Krayin's request-mutating WebForm controller or make an internal HTTP request. The Statamic submission remains in Statamic; the CRM lead and person remain in Krayin.

Configure `source_id`, `type_id`, `pipeline_id` or `owner_id` per form when needed. If they are omitted, the service uses the default/first pipeline, first stage, Website source or first source, first type and an active administrator (or first active user) as owner.

Forms can be stored in Statamic without creating a lead by setting `enabled` or `create_lead` to `false`.

When using a queued connection, run a Laravel queue worker:

```bash
php artisan queue:work
```

## Website Media

Statamic website assets use the dedicated `cms-assets` disk and `public/cms-assets` path, separate from Krayin CRM attachments and user profile images. Asset blueprints include alt text, caption and credit fields.

## ZIP Theme Manager

The Krayin CMS Theme Manager accepts ZIP packages containing:

- A root `theme.json` manifest.
- Antlers templates in `views/templates/`.
- Antlers layouts in `views/layouts/`.
- Antlers partials in `views/partials/`.
- Static assets in `assets/`.
- Optional `README.md` and `preview.png`.

The installer:
- Limits archive size to 15 MB and extracted content to 100 MB.
- Rejects absolute paths, traversal, backslashes, duplicate paths and symlinks.
- Rejects PHP, Composer files, routes, service providers and executable installer scripts.
- Extracts to a private staging directory before installation.
- Stores each theme version separately and publishes its static assets to a versioned public directory.

The manager supports Preview, Activate, Use built-in theme and Remove. Preview URLs are signed and expire after 10 minutes; the preview request also requires an active Krayin account with Statamic's `access cp` permission.

Use the Antlers tag for theme assets:

```antlers
<link rel="stylesheet" href="{{ cms_theme:asset path='css/site.css' }}">
```

A sample source theme is available under `examples/themes/corporate-starter`. See [CMS-THEME-PACKAGE.md](../../../docs/CMS-THEME-PACKAGE.md) for the ZIP contract.

## Public homepage

When Statamic public routes are enabled, the domain root is served by Statamic's native `FrontendController`. The CRM login remains under the configured admin path.

## Current implementation status

Implemented in this feature branch:

- Krayin package registration, menu and ACL.
- CMS overview with native Statamic Control Panel links.
- Shared Krayin guard configuration and inactive-user middleware.
- Separate CMS role/group pivot tables and Statamic user metadata fields.
- Non-destructive starter content installer.
- Pages, Blog, Contact and Website Media structures.
- Statamic form submissions to Krayin lead creation with retry/idempotency records.
- Website settings and public Antlers tags.
- ZIP theme upload, validation, preview, activation, deactivation and removal.
- Theme asset Antlers helper and active theme template resolution.

The package has not yet been installed or executed in the local WAMP runtime. Composer resolution, database migrations, authentication, form processing, theme preview/rendering and regression tests still need to be run on the local feature branch before production use.
