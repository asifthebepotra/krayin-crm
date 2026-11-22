# SA-01 Compatibility Plan: Krayin CRM + Statamic CMS

Status: Source inspection completed; runtime compatibility tests pending.
Audit date: 2026-11-15
Repository: asifthebepotra/krayin-crm
Working branch: audit/SA-01-krayin-statamic-compatibility

## 1. Exact release pins

| Component | Release | Immutable source |
|---|---|---|
| Krayin CRM | v2.2.8 | ed6deebfcb8c616eaf352c893a10a6d8e666fc6e |
| Statamic CMS | v6.42.0 | 3b28f8ceda4e0912ffe51dc7d8d877f0184f23bc |
| Laravel in current Krayin lock | v12.71.0 | 64dc24f5d965add4d402b4b8b64f59b561112af5 |
| PHP platform configured by Krayin | 8.3.30 | composer.json platform config |
| Sanctum in current Krayin lock | v4.3.1 | e3b85d6e36ad00e5db2d1dcc27c81ffdf15cbf76 |
| Symfony HttpFoundation in current Krayin lock | v7.4.23 | 7fb73cffa06e2bcc2cb523fcccb1e8174106b206 |

Krayin tag v2.2.8 resolves to the exact source commit shown above. Statamic tag v6.42.0 resolves to the exact source commit shown above. The user's repository branch 2.2 pointed to Krayin v2.2.8 commit ed6deeb before this documentation was added. For repeatable testing, checkout immutable commits, not moving branch names.

## 2. Dependency intersection assessment

Krayin v2.2.8 declares PHP ^8.3, Laravel ^12.0 and Sanctum ^4.0.

Statamic v6.42.0 declares Laravel ^12.40.0 || ^13.0, Inertia Laravel ^2.0.7 || ^3.0, Guzzle ^6.3 || ^7.0, Symfony HttpFoundation ^7.3.7 || ^8.0 and ext-json.

The current Krayin lock pins Laravel 12.71.0, Guzzle 7.16.1 and Symfony HttpFoundation 7.4.23. These versions satisfy the overlapping declared constraints.

**Declared constraint result: preliminary PASS.** This is not a Composer solver test. Full dependency resolution against the actual Krayin application remains mandatory.

### Inertia investigation

The current Krayin lockfile does not contain inertiajs/inertia-laravel. Statamic v6.42.0 permits either Inertia Laravel 2.0.7+ within v2 or v3.

During dry-run, record which Inertia version Composer selects and verify the Statamic Control Panel runtime against it. If Statamic documentation or runtime tests indicate v3 is unsupported, constrain the application to a supported Inertia 2 release and repeat the solver and runtime tests. Do not infer runtime compatibility from the broad Composer constraint alone.

## 3. Repository audit baseline

- Repository: asifthebepotra/krayin-crm
- Baseline source ref: ed6deebfcb8c616eaf352c893a10a6d8e666fc6e
- Branch: 2.2
- Composer lock blob inspected: fd88b4731c57bffa8a8bc3d125165c5ef40ab3f2
- Composer uses local PSR-4 package namespaces under packages/Webkul/* and manual provider registration in bootstrap/providers.php.

## 4. Source-specific integration risks

### 4.1 Authentication guard and Statamic identity

Krayin configures the default Laravel guard as user, using the session driver and Webkul\\User\\Models\\User provider. The Krayin User model extends Laravel Authenticatable, uses Sanctum HasApiTokens and relates to its own Role model. It does not implement a Statamic user contract in the inspected source.

Required experiment: test Statamic's supported Eloquent/custom user-provider options against Webkul\\User\\Models\\User. Validate Control Panel login/logout, user retrieval, passwords, permissions, disabled-user handling and account changes. If direct provider reuse cannot meet Statamic's user contract safely, use explicit identity mapping and a secure session bridge. Never copy password hashes or bypass authentication checks.

### 4.2 Krayin Bouncer middleware

Krayin AdminServiceProvider registers admin routes behind web, admin_locale and user middleware. The user middleware checks the user guard, active status and route-name ACL, failing closed for unmapped privileged routes.

Statamic Control Panel routes must not be wrapped in Krayin's route-name Bouncer middleware by default. Statamic must enforce CMS authorization using its supported middleware and permissions. Keep the identity bridge separate from CMS authorization.

### 4.3 Role model

Krayin Role stores permission_type and a JSON-cast permissions array. Do not equate Krayin ACL keys with Statamic permissions without an explicit mapping table and negative authorization tests.

### 4.4 Existing WebForm and lead intake

Krayin already includes WebForm. Public routes are under /web-forms. WebFormController::formStore() restricts submitted data to configured exposed attributes, finds a person by exact case-insensitive email, creates/reuses a person, creates leads using Krayin repositories, selects pipeline/stage/source/type defaults and dispatches lead events.

The controller is request-dependent and mutates the global request payload. Do not call it internally or POST back to the same application. Build a service-level adapter for Statamic submissions using existing Krayin repositories/business rules, explicit validated payloads, appropriate lead events/defaults, idempotency and integration logs. Test contact deduplication and owner semantics.

### 4.5 Routes and admin path

Krayin's admin prefix is configurable through APP_ADMIN_PATH, defaulting to admin. Never hardcode /admin. Test Krayin admin routes, Statamic's native Control Panel route (normally /cp, subject to configuration), and Statamic public rendering separately.

### 4.6 Content and filesystem

Use Statamic-native content and asset storage during the first audit. Verify create/read/edit/publish/cache invalidation and backup behaviour. Decide how Git-managed content is deployed and how production edits are preserved.

### 4.7 Statamic licensing

Statamic's inspected package manifest declares a proprietary license. Confirm the intended edition, team-user requirements, production domain licensing and commercial terms before release.

## 5. Runtime execution checklist

### Gate A — Immutable source and baseline

- [ ] Checkout Krayin commit ed6deebfcb8c616eaf352c893a10a6d8e666fc6e.
- [ ] Resolve Statamic tag v6.42.0 / commit 3b28f8ceda4e0912ffe51dc7d8d877f0184f23bc.
- [ ] Record exact PHP, Composer, Node and database versions.
- [ ] Run baseline composer install using untouched Krayin lock.
- [ ] Run composer validate and composer check-platform-reqs.
- [ ] Run existing Krayin tests and capture baseline.

### Gate B — Composer

- [ ] Merge Statamic's documented Composer scripts without dropping Krayin scripts.
- [ ] Add statamic/cms: 6.42.0 in an audit-only working branch.
- [ ] Run composer update statamic/cms -W --dry-run.
- [ ] Review all direct and transitive changes.
- [ ] Record Inertia Laravel selection and compatibility evidence.
- [ ] Complete actual update only after reviewing the dry-run.
- [ ] Run composer validate, composer check-platform-reqs and composer audit.
- [ ] Preserve the combined composer.lock only after acceptance.

### Gate C — Boot and routes

- [ ] Run Laravel package discovery and php artisan about.
- [ ] Run php artisan route:list.
- [ ] Verify Krayin and Statamic service providers.
- [ ] Verify CRM, Control Panel and public routes coexist.
- [ ] Verify route cache and clear.
- [ ] Verify Krayin Bouncer is not accidentally applied to Statamic routes.

### Gate D — Database and storage

- [ ] Run Krayin and Statamic installation/migrations on a clean test database.
- [ ] Verify no duplicate/conflicting migrations.
- [ ] Create and edit pages, collection entries, taxonomy and navigation.
- [ ] Upload and retrieve assets.
- [ ] Restart the application and confirm content persists.
- [ ] Verify publication and cache invalidation.

### Gate E — Authentication and ACL

- [ ] Preserve normal Krayin login/logout.
- [ ] Test native Statamic Control Panel login/logout.
- [ ] Prototype supported custom/Eloquent user provider using Krayin User.
- [ ] Test deactivation propagation and role/permission mapping.
- [ ] Test direct /cp access for unauthenticated users.
- [ ] Test CMS denial for CRM-only users.
- [ ] Test that CMS-only access cannot grant CRM privileges.
- [ ] Confirm a safe bridge is possible without modifying framework internals.

### Gate F — Public rendering and regression

- [ ] Publish a Statamic page and verify public SSR and correct HTTP status.
- [ ] Confirm SEO metadata and no CRM login requirement for public pages.
- [ ] Re-run Krayin dashboard/login/lead/contact/organization/activity regression tests.
- [ ] Compare failures against baseline and record pre-existing failures separately.

## 6. Required deliverables

- KRAYIN-SOURCE-AUDIT.md
- STATAMIC-SOURCE-AUDIT.md
- COMPOSER-COMPATIBILITY.md
- ROUTE-AND-BOOT-COMPATIBILITY.md
- DATABASE-AND-STORAGE-COMPATIBILITY.md
- AUTHENTICATION-AND-ACL-FEASIBILITY.md
- PUBLIC-RENDERING-FEASIBILITY.md
- KRAYIN-REGRESSION-RESULTS.md
- COMPATIBILITY-DECISION.md

## 7. Go / conditional-go / no-go criteria

**GO:** All mandatory runtime gates pass; Composer resolves without unsupported overrides; both applications boot; routes and middleware coexist; CRM regression tests pass; Statamic Control Panel and public rendering work; and a safe identity/permission strategy is demonstrated.

**CONDITIONAL GO:** Only non-critical isolated issues remain with explicit remediation and verification plans. No security, data-integrity or compatibility blocker may be deferred.

**NO-GO:** Unsupported dependency overrides are required; route/middleware conflicts cannot be isolated; authorization requires insecure internal modifications; CRM regressions occur; or a critical security issue remains.

## 8. Current status

| Work item | Status |
|---|---|
| User repository identified and access verified | PASS |
| Krayin v2.2.8 tag/source commit verified | PASS |
| Statamic v6.42.0 tag/source commit verified | PASS |
| Krayin source/package architecture inspected | PASS |
| Krayin authentication/ACL source inspected | PASS |
| Krayin WebForm/lead intake inspected | PASS |
| Declared dependency intersection assessed | PRELIMINARY PASS |
| Composer solver with Statamic installed | NOT RUN |
| Laravel boot/route coexistence | NOT RUN |
| Database/content/storage runtime tests | NOT RUN |
| Shared identity/CP permission prototype | NOT RUN |
| Public Statamic rendering | NOT RUN |
| Krayin regression after installation | NOT RUN |
| Final GO decision | PENDING |

## 9. Execution limitation

GitHub source inspection is complete for the listed files. The GitHub integration can read and update repository content, but it does not execute PHP, Composer, MySQL or the Laravel application. The next step must run in a PHP development environment with this repository cloned. Do not create Webkul/StatamicIntegration until the runtime checklist is completed and COMPATIBILITY-DECISION.md records GO.

## 10. Updated exact installation sequence for this Krayin source

This section supersedes any shorter generic Composer example above. It reflects the actual Krayin Composer scripts and the current Statamic 6 installation guide.

### 10.1 Work only on the audit branch

- Confirm the current branch is `audit/SA-01-krayin-statamic-compatibility`.
- Confirm `composer.json` and `composer.lock` are clean before editing.
- Preserve an untouched baseline copy of both files or a Git commit.
- Do not run dependency installation on `main` or the local `master` branch.

### 10.2 Add exact dependency requirements without resolving them yet

Use exact Statamic and a supported Inertia 2 constraint:

```bash
composer require statamic/cms:6.42.0 inertiajs/inertia-laravel:^2.0.7 --no-update
```

Why pin Inertia 2: Statamic's current v6 installation documentation explicitly says Inertia 3 is not currently supported, even though the v6.42.0 package manifest declares a broad `^2.0.7 || ^3.0` constraint. The inspected Krayin lockfile does not currently contain Inertia Laravel. The root constraint prevents Composer selecting Inertia 3 for this compatibility run.

### 10.3 Merge Statamic's Composer scripts; do not overwrite Krayin's

Statamic's official existing-Laravel installation guide requires:

- `pre-update-cmd`: `Statamic\\Console\\Composer\\Scripts::preUpdateCmd`
- `post-autoload-dump`: keep Laravel ComposerScripts and `@php artisan package:discover --ansi`, then add `@php artisan statamic:install --ansi`.

Krayin already has a `post-autoload-dump` array with Laravel ComposerScripts and `package:discover`. Add the Statamic command to that existing array; do not replace or duplicate the existing commands. Preserve `post-root-package-install` and `post-create-project-cmd` too.

Before resolving dependencies:

```bash
php artisan config:clear
composer validate
```

### 10.4 Inspect Composer's proposed changes

```bash
composer update statamic/cms inertiajs/inertia-laravel -W --dry-run
```

Capture and review:
- Laravel Framework remains on a compatible Laravel 12 release.
- Inertia Laravel resolves to 2.x, not 3.x.
- No Krayin dependencies are removed.
- No unrelated major-version upgrades are proposed.
- Symfony, Guzzle, Flysystem, image-processing and authentication dependencies resolve.
- No PHP platform requirement is violated.

A dry run is only a solver proposal. It does not prove application boot or Control Panel compatibility.

### 10.5 Actual install (only after the dry run is reviewed)

```bash
composer update statamic/cms inertiajs/inertia-laravel -W
```

Then:

```bash
composer validate
composer check-platform-reqs
composer show laravel/framework
composer show statamic/cms
composer show inertiajs/inertia-laravel
composer audit
php artisan package:discover
php artisan about
php artisan route:list
```

Review the generated `config/statamic` and new directories. The existing Laravel installation will not automatically include the standard Statamic pages collection, entries, or views; those are deliberately configured in later audit tests.

## 11. Statamic identity and permission prototype

The pinned Statamic source has an Eloquent user repository that derives the model from `statamic.users.guards.cp` -> Laravel guard provider -> provider model. It also wraps generic Eloquent models through `UserRepository::fromUser()`. Therefore prototype this supported shared-guard approach before building a separate login bridge.

Proposed prototype configuration (subject to runtime verification):

- Statamic user repository: `eloquent`.
- Statamic CP guard: Krayin's `user` guard.
- Statamic frontend guard: set deliberately and test; do not leave a guard name that does not exist.
- Krayin `user` guard provider remains `Webkul\\User\\Models\\User`.
- Keep Statamic CP middleware groups native.
- Add a dedicated integration check on authenticated CP requests that rejects inactive Krayin users and invalidates the session.

Do not attach Krayin's route-name Bouncer to all Statamic CP routes. Statamic evaluates its own permission `access cp` through its permission Gate.

### 11.1 CMS role storage

- Keep Statamic role and group definitions file-backed initially.
- Configure distinct CMS relationship pivot tables, e.g. `statamic_role_user` and `statamic_group_user`.
- Do not point Statamic at Krayin's `roles`, `groups` or `user_groups` tables.
- Review the output of `php please auth:migration` before running migrations. Ensure generated table names use the configured CMS pivots.

### 11.2 User table columns

Statamic's Eloquent user adapter reads/writes `preferences` and updates `last_login`. The inspected Krayin schema lacks those columns. Add them with an integration-owned migration and casts on the Krayin User model, after reviewing the migration generator output.

Statamic 2FA fields are not present in Krayin's user schema. Disable Statamic 2FA for the first prototype/release; if it is later enabled, add and test the required fields through an integration migration.

### 11.3 Account-management restrictions

Krayin must remain the account source of truth. Statamic's native user creation writes through its configured user repository, so it must not create Krayin accounts with a missing required Krayin `role_id`.

For the first release:
- Do not allow Statamic to create/delete employee accounts.
- Do not allow Statamic to change Krayin passwords or CRM roles.
- Make CRM identity attributes such as name/email/status read-only or keep them out of editable CMS blueprints.
- If CMS role assignment is enabled in Statamic, verify it changes only the CMS role pivot and not Krayin's `role_id`.

### 11.4 Mandatory identity tests

- Krayin login allows access to Statamic CP in the same browser session.
- Statamic login uses the same underlying Krayin user ID.
- Statamic logout invalidates the shared session; verify Krayin is also logged out.
- Krayin logout invalidates the shared session; verify Statamic is also logged out.
- Disabled Krayin account is denied on the next Statamic CP request.
- CMS-only permissions do not grant CRM ACL permissions.
- CRM-only users without `access cp` are denied from the CP.
- Statamic user management cannot create or mutate Krayin accounts outside Krayin's lifecycle.

## 12. Existing-app content and route checks

The official Statamic existing-Laravel guide notes that the app needs a Pages collection, entries, views and a layout configured manually. Create a minimal Pages collection using the documented tree route `{parent_uri}/{slug}`, then a `default` template and `layout` view for the first smoke test.

Statamic relies on a catch-all frontend route. Verify Krayin has no earlier catch-all route that captures public URLs. Existing explicitly declared routes take precedence; test both existing Krayin routes and Statamic's public content routes.

## 13. Licensing gate

The Statamic core package declares a proprietary license. Confirm the required Statamic edition and commercial terms before production, especially because a multi-user CMS with role-based access is part of the intended product.