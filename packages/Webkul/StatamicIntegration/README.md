# Krayin Statamic CMS Integration

This package adds the CMS integration entry point to Krayin CRM and uses Statamic as the content engine and native editorial Control Panel.

## Responsibilities

- Register the CMS section in Krayin navigation and ACL.
- Expose a Krayin CMS overview page.
- Link to Statamic's native Control Panel.
- Keep CMS-specific integration code under this package.
- Leave Statamic in charge of collections, entries, blueprints, assets, forms, publishing and Antlers rendering.
- Leave Krayin in charge of CRM accounts, roles, leads, contacts and sales workflows.

## Current scope

The first module slice provides the Krayin package foundation and CMS dashboard. It does not duplicate Statamic content models or implement a separate page editor.

## Dependencies

- PHP 8.3+
- Krayin CRM 2.2 / Laravel 12
- Statamic CMS 6.42.0
- Inertia Laravel 2.x

## Routes

- `GET {APP_ADMIN_PATH}/cms` — CMS overview.
- `GET {APP_ADMIN_PATH}/cms/open` — opens the native Statamic Control Panel.

The `APP_ADMIN_PATH` setting is respected.

## Next module increments

1. Statamic installation and shared Krayin user guard prototype.
2. Default Pages and Blog collections, blueprints and initial Antlers views.
3. CMS website settings and public homepage ownership.
4. Statamic form submission adapter to Krayin lead intake.
5. Secure ZIP theme installer and theme-aware template resolution.

Theme ZIPs must never execute PHP, Composer scripts, service providers or route registrations.