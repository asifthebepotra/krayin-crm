# Statamic CMS Source Audit

**Selected release:** Statamic CMS v6.42.0
**Immutable source commit:** 3b28f8ceda4e0912ffe51dc7d8d877f0184f23bc
**Audit date:** 2026-11-15
**Scope:** Package manifest and release source identification. Runtime integration is not yet tested.

## 1. Release verification

The Git tag refs/tags/v6.42.0 resolves to commit 3b28f8ceda4e0912ffe51dc7d8d877f0184f23bc in statamic/cms. Use this immutable commit for repeatable source review.

Source: https://github.com/statamic/cms/tree/v6.42.0
Composer manifest: https://github.com/statamic/cms/blob/v6.42.0/composer.json

## 2. Composer manifest findings

The v6.42.0 manifest identifies the package as statamic/cms and declares a proprietary license.

Relevant requirements:

| Dependency | Constraint |
|---|---|
| Laravel Framework | ^12.40.0 or ^13.0 |
| Inertia Laravel | ^2.0.7 or ^3.0 |
| Guzzle | ^6.3 or ^7.0 |
| Symfony HttpFoundation | ^7.3.7 or ^8.0 |
| Symfony Lock | ^7.0.3 or ^8.0 |
| Symfony VarExporter | ^7.0.3 or ^8.0 |
| Symfony YAML | ^7.0.3 or ^8.0 |
| Intervention Image | ^3.9.1 or ^4.0 |
| Laravel Prompts | ^0.3.0 |
| ext-json | Required |

The Krayin source declares PHP ^8.3 and Laravel ^12.0; its lock pins Laravel 12.71.0 and Symfony HttpFoundation 7.4.23. Those versions fall within the shared declared constraints. Composer must still resolve and verify all additional transitive dependencies.

## 3. Laravel package registration

The Statamic Composer manifest declares Laravel package auto-discovery for Statamic\\Providers\\StatamicServiceProvider and an alias named Statamic. Krayin's composer.json sets Laravel dont-discover to an empty list, so there is no declared root-level exclusion of Statamic package discovery.

Krayin also manually registers its own Webkul providers in bootstrap/providers.php. Verify package discovery and Laravel's provider manifest in the integrated application. Do not add Statamic's provider manually unless the supported installation path requires it; avoid double registration.

## 4. Inertia compatibility question

Statamic v6.42.0's manifest allows inertiajs/inertia-laravel ^2.0.7 or ^3.0. The inspected Krayin composer.lock does not currently contain inertiajs/inertia-laravel.

The first combined Composer dry run must record the selected Inertia version. Then verify the Statamic Control Panel against the exact resolved version using official Statamic documentation and runtime tests. If v3 is not supported by this Statamic release in practice, constrain the root application to a compatible v2 release and repeat the full dependency resolution. Do not resolve this by blindly forcing a dependency.

## 5. Existing Laravel installation

Statamic documents installation into an existing Laravel application. This is the correct installation model for the selected single-application architecture. Follow the official installation guide for the pinned release, merge required scripts and configuration with Krayin's existing Composer setup, and retain all existing Krayin scripts.

Installation guide: https://statamic.dev/getting-started/installing/laravel

## 6. Content and control-panel ownership

Statamic should retain ownership of its native collections, structures, taxonomies, blueprints, assets, navigation, forms, publishing and template rendering. Krayin remains the owner of CRM users, roles, leads, contacts, organisations and business workflows. The custom integration package will bridge only the necessary integration operations.

Use Statamic's native content and asset lifecycle. Do not create duplicate Krayin tables for pages, entries, assets or publishing state.

## 7. Authentication and ACL feasibility

The package manifest alone does not establish compatibility with Krayin's custom user model or Bouncer authorization middleware. Statamic Control Panel authentication, user repository, permission registration and middleware must be inspected in the pinned source and tested in the combined app.

Specifically verify:

- Can Statamic use or adapt Webkul\\User\\Models\\User through a supported provider mechanism?
- Does the Krayin User model satisfy the user methods/contracts expected by Statamic?
- How does Statamic associate permissions with the authenticated user?
- Can the CP session be established and invalidated securely?
- Can Krayin's disabled-user status revoke CMS access?
- Can CMS permissions be enforced without applying Krayin's Bouncer route middleware to Statamic routes?

Do not copy password hashes or bypass either system's authentication/authorization mechanisms.

## 8. Licensing gate

The package manifest declares license=proprietary. Confirm the appropriate Statamic edition and commercial licensing terms before production deployment, particularly the number of team users and intended production usage. A successful Composer installation is not a substitute for confirming license entitlement.

## 9. Audit status

| Item | Status |
|---|---|
| v6.42.0 tag and source commit verified | PASS |
| Composer constraints inspected | PASS |
| Statamic Laravel package provider declaration inspected | PASS |
| Inertia selection in combined dependency graph | PENDING |
| Auth/user repository source audit | PENDING |
| Permission source audit | PENDING |
| Route and middleware coexistence | PENDING |
| CMS content publication in combined app | PENDING |
| Public rendering in combined app | PENDING |
| Licensing entitlement confirmed | PENDING |

**Conclusion:** Statamic v6.42.0 has a declared dependency intersection with the Krayin v2.2.8 Laravel 12.71.0 baseline. Runtime compatibility remains unproven until the combined Composer installation and application tests pass.