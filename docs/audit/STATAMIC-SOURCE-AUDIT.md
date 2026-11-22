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

## 10. Deeper authentication and role source audit (v6.42.0)

### 10.1 Direct Eloquent user wrapping is supported

The pinned Statamic `UserRepositoryManager::createEloquentDriver()` chooses the Eloquent model from:

- `statamic.users.guards.cp`
- `auth.guards.<selected-guard>.provider`
- `auth.providers.<provider>.model`

Statamic's `AuthEloquentUserRepository::fromUser()` accepts an existing `StatamicContractsAuthUser`, a model exposing `toStatamicUser()`, or a generic `IlluminateDatabaseEloquentModel`. A normal Eloquent Krayin User model therefore qualifies for the generic wrapper.

The native CP middleware `AuthGuard` sets Laravel's default guard using `statamic.users.guards.cp`. The Statamic CP login controller uses Laravel's current default guard, and its `Authorize` middleware checks the Statamic permission `access cp` through the Statamic user facade/Gate. This gives us a plausible supported configuration:

- Statamic repository: `eloquent`.
- Statamic CP guard: Krayin's `user` guard.
- Laravel `user` guard provider model: Krayin's `Webkul\\User\\Models\\User`.
- Statamic converts the authenticated Krayin model to its own Eloquent User wrapper.

With the same Laravel session and guard, the intended outcome is one identity and one login session, not duplicate passwords. The CP guard setting must be applied before resolving the Statamic user on CP requests. This is a source-backed design candidate, not yet a runtime pass.

### 10.2 Keep CMS permissions separate from Krayin CRM permissions

Statamic's own permission Gate adapter calls `User::fromUser($user)`, then checks the Statamic user's `isSuper()` and `hasPermission()`. The default Statamic Eloquent User obtains its permissions through Statamic role and user-group relationships.

Krayin's `role_id` and `hasPermission()` are a different permission model. Do not map every Krayin permission string directly to Statamic permission names.

The least invasive design candidate is:
- Krayin continues to own login identity, user status and CRM roles.
- Statamic continues to own CMS permission definitions and CMS roles.
- Existing Krayin user IDs are reused by Statamic's Eloquent user adapter.
- Statamic role/group pivot tables use distinct configured table names, not Krayin's `user_groups`.
- CMS roles are assigned only to existing Krayin users.
- Krayin CRM ACL and Statamic CMS ACL remain independent.

### 10.3 Statamic's native CP authorization sequence

Statamic's Control Panel provider defines:
- `statamic.cp`: session, CSRF, Inertia, default guard selection and request setup.
- `statamic.cp.authenticated`: session authentication, localization, Statamic `Authorize`, site selection, permissions and preferences boot.

The Statamic `Authorize` middleware checks `User::current()` and requires `access cp`. Krayin's route-name Bouncer is not part of this middleware group and should not be added indiscriminately.

### 10.4 User-storage and migration cautions

The pinned Statamic `config/users.php` defaults to the Eloquent repository, with `users` as its user table, `role_user` and `group_user` as relationship pivot tables, and file-backed roles/groups when their table settings are false.

For this Krayin application:
- Reuse the existing `users` table and the Krayin `user` guard.
- Keep Statamic role/group definitions file-backed initially to avoid Krayin's `roles` and `groups` tables.
- Configure separate pivot names such as `statamic_role_user` and `statamic_group_user`.
- Confirm required pivot schema and migrations in runtime.
- Add or deliberately disable Statamic user fields absent from Krayin's users table, especially `preferences`, `last_login` and two-factor fields.
- Do not let Statamic's native user-create flow create new CRM users. Krayin remains the account source of truth.

Statamic's native user edit flow can also write profile data to the underlying Eloquent model. Make name/email read-only or restrict the Users area so CRM identity fields remain managed from Krayin. CMS role assignment for existing users may be permitted after tests.

### 10.5 Account status is a separate authorization requirement

Krayin's Bouncer checks the `status` flag and invalidates sessions for disabled users on Krayin admin routes. Statamic's own CP authorization checks Statamic permissions; it does not automatically run Krayin's Bouncer. Add a dedicated middleware to the authenticated Statamic CP pipeline that rejects/invalidate sessions for a disabled Krayin user.

### 10.6 Runtime prototype required

Test this exact path before deciding the bridge:
1. Sign into Krayin through the `user` guard.
2. Open Statamic `/cp` in the same browser session without a second login.
3. Confirm Statamic resolves the same Krayin user ID.
4. Confirm Statamic can evaluate `access cp` using a CMS role.
5. Confirm Krayin CRM permissions remain unchanged.
6. Disable the Krayin user and confirm the next CP request is denied and the session is invalidated.
7. Confirm Statamic user CRUD cannot create a Krayin account with missing `role_id`.
8. Confirm any user profile/permission update does not corrupt CRM user records.

## 11. Deeper template and theme source audit (v6.42.0)

### 11.1 Native website rendering

The pinned Statamic `FrontendController` creates a `Statamic\\View\\View` from a template handle and layout. The Statamic view resolves:
- templates through `templates.<handle>` when present, otherwise the given handle;
- layouts through `layouts.<handle>` when present, otherwise the given handle.

Rendering ultimately goes through Laravel's view factory/finder and the Statamic Antlers engine. Partial/include tags also resolve Laravel views.

This provides a viable extension point for a single active website theme: a theme-aware Laravel view-finder/path strategy can supply the active theme's templates, layouts and partials while keeping Statamic's content/rendering pipeline intact. It must be tested for nested partials, includes, layouts, Live Preview and cache behaviour. Do not replace Statamic's FrontendController or Antlers engine.

### 11.2 What Statamic's existing Theme class does not provide

The Statamic v6.42.0 `Theme` tag is deprecated and is an asset-path helper. It does not provide an active website theme registry, ZIP theme installation or template switching. The CP Themes controller in this release returns marketplace theme data; it is not a website template package manager.

Therefore our ZIP theme installer and active website theme selection remain custom integration functionality. Treat this as a managed template package, not arbitrary executable code.

### 11.3 Theme architecture candidate

A theme ZIP can contain:
- `theme.json` manifest and preview image;
- Antlers `templates/`, `layouts/` and `partials/`;
- static assets;
- optional documented blueprint/field configuration.

The installer validates the archive and extracts to a controlled directory. A custom theme-aware view finder makes that directory active for public rendering. Assets are served from a controlled `public/themes/<slug>/` path using a custom asset helper/tag or explicit Vite integration; do not depend on the deprecated Statamic Theme tag for active theme management.

Because changing Laravel view-finder locations on a singleton can leak state between requests in long-lived workers, the implementation must either use a request-aware finder or reliably scope/reset the active theme context. The initial PHP-FPM prototype still needs the same correctness tests.

Do not allow executable PHP, Composer scripts, arbitrary service providers or route registration inside uploaded theme ZIPs.

### 11.4 Theme acceptance test

- Install a test theme ZIP.
- Activate it without changing content entries.
- Render a page with the theme's template and layout.
- Render nested partials/includes from the same active theme.
- Verify theme assets return successfully.
- Preview before activation.
- Reject traversal, symlink and executable payloads.
- Roll back to the previous theme.
- Verify inactive theme removal does not delete content or assets shared by another active package.

## 12. Licensing and edition

The Statamic v6.42.0 package manifest declares a proprietary license. Confirm commercial edition and production licensing requirements before release. Statamic Pro is required for the role/user management features we intend to use in the native Control Panel; confirm the exact entitlement and team-user terms for the intended deployment.


## 10. Official Statamic 6 existing-Laravel installation guide

The current official guide for installing Statamic 6 into an existing Laravel app confirms:

- Statamic 6 supports Laravel 12 and 13.
- Inertia 3 is not currently supported in Statamic 6; the app must use Inertia 2.
- Add `Statamic\\Console\\Composer\\Scripts::preUpdateCmd` to `pre-update-cmd`.
- Add `@php artisan statamic:install --ansi` to the existing `post-autoload-dump` script after Laravel's Composer scripts and package discovery.
- Install `statamic/cms` through Composer.
- Depending on the app's user setup, generate Statamic auth migrations with `php please auth:migration`.
- An existing Laravel app does not receive a default pages collection, content entries, or views automatically. These must be configured manually.
- Statamic uses flat-file content by default in this setup.
- Statamic uses a catch-all public route, so an existing application catch-all route can prevent content routing.

Official guide: https://statamic.dev/getting-started/installing/laravel

## 11. Shared authentication and user-storage source details

The pinned Statamic v6.42.0 source supports an Eloquent user repository whose model is chosen from the configured Control Panel guard's Laravel provider. Its `UserRepository::fromUser()` wraps generic Eloquent models as a Statamic user object. This makes Krayin's `Webkul\\User\\Models\\User` a plausible identity model.

Relevant source:
- `src/Auth/UserRepositoryManager.php`
- `src/Auth/Eloquent/UserRepository.php`
- `src/Auth/Eloquent/User.php`
- `src/Providers/AuthServiceProvider.php`
- `src/Http/Middleware/CP/AuthGuard.php`
- `src/Http/Middleware/CP/Authorize.php`
- `src/Providers/CpServiceProvider.php`
- `config/users.php`
- `config/cp.php`

Statamic CP routes use their own middleware groups. The CP guard is selected from `statamic.users.guards.cp`; the native authorization middleware requires Statamic's `access cp` permission. The integration should use Krayin's `user` guard but keep the Statamic authorization pipeline intact.

Statamic's Eloquent user adapter reads/writes preferences and updates `last_login`. The Krayin user schema audited for this project does not have those fields. Add them with integration-owned migrations and model casts, or explicitly disable functionality that would try to write unavailable fields.

Statamic's role/group user pivots are configured separately from its role/group definitions. Keep role/group definitions file-backed at first and give the CMS pivots distinct table names. Do not point Statamic at Krayin's own `roles`, `groups` or `user_groups` tables.

Official references:
- [Statamic existing Laravel user/guard notes](https://statamic.dev/knowledge-base/tips/using-an-independent-authentication-guard)
- [Statamic Eloquent user storage](https://statamic.dev/knowledge-base/tips/storing-users-in-a-database)

## 12. Template and theme source details

The pinned source shows:
- `FrontendController` creates a Statamic `View` with a template and layout.
- `Statamic\\View\\View` resolves template and layout handles through Laravel's view factory.
- The Antlers engine renders the resolved view.
- Partial and Include tags also use Laravel's view factory.

This supports a theme-aware Laravel view-finder integration candidate while preserving Statamic's content and Antlers renderer. It does not prove a specific active-theme resolver is runtime-safe. Test nested partials, includes, cache, Live Preview and request isolation before implementation.

The existing Statamic `Theme` tag is deprecated and is an asset helper, not a theme installer or active website theme manager. Statamic's CP ThemeController in this release returns marketplace information rather than installing arbitrary website template ZIP packages.

Relevant source:
- `src/Http/Controllers/FrontendController.php`
- `src/View/View.php`
- `src/View/Antlers/Engine.php`
- `src/Tags/Theme.php`
- `src/Http/Controllers/CP/Themes/ThemeController.php`

The custom ZIP manager and active theme selection therefore remain integration responsibilities. Never execute PHP, Composer scripts, route providers or service providers supplied inside uploaded theme archives.

## 13. Licensing

Statamic's v6.42.0 package manifest declares a proprietary license. Confirm the selected edition and production licensing requirements before release. Multi-user role-based editing requires confirming the Statamic Pro entitlement and team-user terms with Statamic's current licensing documentation.

## 14. Runtime status

The source inspection supports a shared-guard prototype and a custom view-resolution prototype, but it does not establish that either works in the integrated app. Composer resolution, Control Panel boot, database migrations, shared login, permissions, inactive-user revocation and public rendering remain mandatory runtime gates.