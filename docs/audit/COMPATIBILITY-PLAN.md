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