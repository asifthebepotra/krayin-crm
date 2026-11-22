# Krayin Statamic CMS Integration

This package adds a CMS module to Krayin CRM and uses Statamic as the content engine and native editorial Control Panel.

## Responsibilities

- Register the CMS section in Krayin navigation and ACL.
- Expose a Krayin CMS overview page.
- Open Statamic's native Control Panel from Krayin.
- Share the existing Krayin employee session for Statamic authentication.
- Keep Statamic CMS roles separate from Krayin CRM roles.
- Make Statamic serve the public website homepage at `/`.
- Install a starter Pages, Blog, Contact and Media structure.
- Send configured Statamic form submissions to Krayin's existing lead workflow.
- Keep CMS-specific integration code under this package.

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

## First installation

The root Krayin `composer.json` includes the pinned Statamic package, Inertia 2 requirement and this package's PSR-4 namespace.

For a fresh checkout without `vendor/`, the first dependency update must run without Composer scripts:

```bash
composer update statamic/cms inertiajs/inertia-laravel --with-all-dependencies --no-scripts
php artisan package:discover --ansi
php artisan statamic:install --ansi
```

Then run the Krayin and integration migrations:

```bash
php artisan migrate
```

Do not use `composer install` before the lockfile includes Statamic and Inertia. Once the first update has completed, the generated lockfile is current and can be used normally.

## Install the starter website

Run:

```bash
php artisan statamic-integration:install-starter-content
```

The command copies files only when their destination does not already exist. Existing content, forms, blueprints, roles and views are preserved.

The starter package includes:

- Pages collection with Home, Blog and Contact pages.
- Dated Blog collection.
- Categories and Tags taxonomies.
- Website Media asset container using a separate `cms-assets` filesystem disk.
- Page, Blog Post, taxonomy, asset and Contact form blueprints.
- Contact form with native Statamic validation and submission storage.
- Default, Home, Blog listing, Blog detail and Contact Antlers templates.
- A responsive starter layout.

The installer also assigns the Statamic `CMS Administrator` role to active Krayin users whose existing Krayin role is configured with `permission_type = all`. It only inserts rows in the Statamic role pivot; it never changes Krayin's `role_id` or CRM permissions.

After installation, review the generated content and set the website name in the Statamic Control Panel.

## Shared identity and permissions

Statamic's Eloquent user repository is configured to use Krayin's existing `user` session guard and a small compatible subclass of `Webkul\\User\\Models\\User`.

- Krayin remains the source of employee accounts, passwords and active status.
- Statamic controls its own CMS roles and content permissions.
- CMS role and group pivots use `statamic_role_user` and `statamic_group_user`.
- Krayin's `roles`, `groups` and `user_groups` tables are not reused.
- A Statamic CP middleware checks the Krayin user `status` flag.
- Statamic's own user administration is not granted to the starter CMS Administrator role.

The initial migration adds Statamic's `preferences` and `last_login` fields to Krayin's `users` table. Statamic two-factor authentication is disabled until its required columns are implemented separately.

## Contact form to CRM lead integration

The starter Contact form is enabled in `src/Config/cms.php`:

```php
'forms' => [
    'contact' => [
        'enabled' => true,
        'create_lead' => true,
        'field_map' => [
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'company' => 'company',
            'message' => 'message',
        ],
        'lead_title_prefix' => 'Website enquiry',
        'source_name' => 'Website',
        'type_name' => null,
        'pipeline_id' => null,
        'source_id' => null,
        'type_id' => null,
        'owner_id' => null,
    ],
],
```

On a stored Statamic submission:

1. The integration listener reads the configured form mapping.
2. A unique queue job processes the submission.
3. The integration finds an existing person by exact email or creates a person through Krayin's PersonRepository.
4. It selects the configured/default pipeline, stage, lead source, lead type and owner.
5. It creates the lead through Krayin's LeadRepository.
6. The integration log stores the CRM lead ID and processing result.

The integration does not call Krayin's WebForm controller or make an internal HTTP request. It uses the CRM repository layer. A unique Statamic submission ID and transaction protect retries from producing duplicate leads.

In production, run Laravel's queue worker if the application uses a queued connection:

```bash
php artisan queue:work
```

Configure `source_id`, `type_id`, `pipeline_id` or `owner_id` in the form mapping when the business wants a specific CRM assignment. If omitted, the integration uses the default pipeline and first stage, the configured Website source or first available source, first available lead type, and an active Krayin administrator as owner.

Set `enabled` or `create_lead` to `false` for any Statamic form that should collect submissions without creating CRM leads.

## Public homepage

Krayin's front package normally registers `/` as a redirect to CRM login. When Statamic is installed and public routes are enabled, this package registers an explicit root route to Statamic's native `FrontendController`. The CRM login remains under the configured admin path.

## Current implementation scope

Implemented in this feature branch:

- Krayin package registration, admin navigation and ACL.
- CMS overview and native Statamic Control Panel link.
- Shared Krayin user guard configuration and active-user middleware.
- Separate CMS role/group pivot tables and user profile columns.
- Non-destructive starter content installer.
- Pages, Blog, Contact and Website Media starter structures.
- Native Statamic contact form connected to Krayin lead creation.
- Idempotency log and queued retry handling.
- Starter templates using Statamic Antlers.

Next implementation increments:

1. Runtime test and refine the shared identity/permission configuration.
2. CMS website settings and SEO defaults.
3. Theme ZIP validation, install, preview and activation.
4. Theme-aware Antlers template and asset resolution.
5. CMS integration logs and retry screen inside the Krayin dashboard.

Theme ZIPs must never execute PHP, Composer scripts, service providers or route registrations.