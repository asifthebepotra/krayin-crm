# Krayin Statamic CMS Integration

This package adds a CMS module to Krayin CRM and uses Statamic as the content engine and native editorial Control Panel.

## Responsibilities

- Register the CMS section in Krayin navigation and ACL.
- Expose a Krayin CMS overview page.
- Link to Statamic's native Control Panel.
- Make Statamic serve the public website homepage at `/` after it is installed.
- Install a safe starter content structure for Pages and Blog.
- Keep CMS-specific integration code under this package.
- Leave Statamic in charge of collections, entries, blueprints, assets, forms, publishing and Antlers rendering.
- Leave Krayin in charge of CRM accounts, roles, leads, contacts and sales workflows.

## Dependencies

- PHP 8.3+
- Krayin CRM 2.2 / Laravel 12
- Statamic CMS 6.42.0
- Inertia Laravel 2.x

## Krayin routes

The module respects Krayin's configurable `APP_ADMIN_PATH`.

- `GET {APP_ADMIN_PATH}/cms` — CMS overview.
- `GET {APP_ADMIN_PATH}/cms/open` — opens the native Statamic Control Panel.

## Install the module dependencies

The root Krayin `composer.json` includes Statamic and the integration package autoload mapping. The first install should be run with scripts suppressed so the package is installed before the Statamic Artisan commands are invoked:

```bash
composer update statamic/cms inertiajs/inertia-laravel --with-all-dependencies --no-scripts
php artisan package:discover --ansi
php artisan statamic:install --ansi
```

After this succeeds, Statamic's Composer hooks are available for subsequent Composer operations.

## Install the starter website structure

Run:

```bash
php artisan statamic-integration:install-starter-content
```

This copies default content files only when the destination does not already exist. Existing collections, entries, blueprints and views are never overwritten.

The starter structure includes:

- Pages collection with a root Home page and a Blog listing page.
- Dated Blog collection.
- Categories and Tags taxonomies.
- Page, Blog Post and taxonomy blueprints.
- Default, Home, Blog listing and Blog detail Antlers templates.
- A simple responsive layout.

Review the generated files and update the site name/settings in the Statamic Control Panel.

## Public homepage

Krayin's front package normally registers `/` as a redirect to CRM login. When Statamic is installed and public routes are enabled, this package registers an explicit root route to Statamic's native `FrontendController`. The CRM login remains under the configured admin path.

## Current scope

Implemented foundation:
- Krayin package registration, admin navigation and ACL.
- CMS overview and native Control Panel link.
- Conditional public homepage route.
- Non-destructive starter content installer.
- Pages and Blog starter content, blueprints and templates.
- Feature tests for the CMS dashboard.

Not implemented yet:
- Shared Krayin/Statamic authentication and CMS role mapping.
- Website settings persistence.
- Contact form to Krayin lead adapter.
- ZIP theme installation/activation.
- Theme-specific Antlers view resolution.

Theme ZIPs must never execute PHP, Composer scripts, service providers or route registrations.