# Krayin Statamic CMS Module

This package adds the CMS entry point and Krayin-side integration layer for Statamic CMS.

## Responsibilities

- Register a CMS section in the Krayin admin navigation.
- Apply Krayin route-level access control to the module entry point.
- Link administrators to the native Statamic Control Panel.
- Provide the package boundary for website settings, CMS identity/permissions and CRM form integration.

## Ownership boundaries

- Statamic owns content collections, entries, blueprints, assets, navigation, forms, revisions and publishing.
- Krayin owns employee accounts, CRM roles, leads, contacts, organizations and sales workflows.
- This package owns the integration between the two systems.

The package does not replace the Statamic editor or duplicate Statamic content tables.

## Initial module route

The module dashboard is available at:

```text
/{APP_ADMIN_PATH}/cms
```

The default admin prefix is `/admin`. The Statamic Control Panel route is read from `statamic.cp.route` and defaults to `/cp`.

## Installation

The application root registers the `Webkul\\Cms` PSR-4 namespace and `CmsServiceProvider` in `bootstrap/providers.php`. Statamic is installed as an application dependency at the pinned version in the root `composer.json`.

After updating Composer dependencies, run package discovery and the Statamic installation command. See the repository's deployment notes for the complete install sequence.
