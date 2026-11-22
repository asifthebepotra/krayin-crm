# Krayin CRM Source Audit

**Repository:** `asifthebepotra/krayin-crm`  
**Source ref audited:** branch `2.2`, initially pointing to Krayin v2.2.8 commit `ed6deebfcb8c616eaf352c893a10a6d8e666fc6e`  
**Audit date:** 2026-11-22  
**Scope:** Read-only inspection of source files in the connected GitHub repository. No runtime or Composer execution was performed by this source audit.

## 1. Composer and package architecture

The inspected root `composer.json` declares:

- PHP `^8.3`
- Laravel Framework `^12.0`
- Laravel Sanctum `^4.0`
- PSR-4 package namespaces under `Webkul\\<Package>\\`
- A local path repository for `packages/*/*`
- Laravel Composer package discovery enabled (no package exclusions in `dont-discover`).

The inspected lockfile pins Laravel Framework `v12.71.0`, Sanctum `v4.3.1`, Guzzle `7.16.1`, Flysystem `3.36.0`, and Symfony Console/HttpFoundation `v7.4.23`. The PHP platform is pinned to `8.3.30`.

Krayin registers its package providers explicitly in `bootstrap/providers.php`. Keep this structure intact and verify Statamic's auto-discovered provider after installing the package. Do not manually register Statamic twice.

The root `composer.json` includes the development dependency `krayin/krayin-package-generator: dev-master`; retain it only as part of the existing development setup and assess reproducibility separately. It is not a production dependency.

## 2. Authentication and user model

### Existing Krayin identity

Source files inspected:

- `config/auth.php`
- `packages/Webkul/User/src/Models/User.php`
- `packages/Webkul/User/src/Models/Role.php`
- `packages/Webkul/User/src/Repositories/UserRepository.php`
- `packages/Webkul/Admin/src/Http/Controllers/User/SessionController.php`

Krayin configures the default guard as `user`, using Laravel's session driver and Eloquent provider model `Webkul\\User\\Models\\User`.

The Krayin User model extends Laravel's `Authenticatable`, uses Sanctum's `HasApiTokens`, and exposes `role()`, `groups()` and `hasPermission()`. Krayin's login controller uses the `user` guard, validates the account's active `status`, applies failed-login throttling, and invalidates the session on logout.

### User schema

Migrations inspected:

- `packages/Webkul/User/src/Database/Migrations/2021_03_12_074857_create_users_table.php`
- `packages/Webkul/User/src/Database/Migrations/2021_03_12_074597_create_roles_table.php`
- `packages/Webkul/User/src/Database/Migrations/2021_03_12_074578_create_groups_table.php`
- `packages/Webkul/User/src/Database/Migrations/2021_03_12_074867_create_user_groups_table.php`

The Krayin `users` table includes:

- auto-increment integer `id`
- `name`
- unique `email`
- nullable `password`
- `status` (default 0)
- required `role_id` foreign key to Krayin `roles.id`
- `remember_token`
- timestamps

The Krayin Role table stores `permission_type` and JSON `permissions`. Krayin user-to-group assignments use `user_groups`; user-to-role assignment uses the `role_id` column on `users`.

The inspected Krayin users schema does not include Statamic's Eloquent adapter fields `preferences`, `last_login`, or optional two-factor fields. The Statamic adapter/installation must add only the required CMS-compatible fields through integration migrations, or keep the related features disabled until supported.

## 3. Admin route and ACL behaviour

Source files inspected:

- `packages/Webkul/Admin/src/Providers/AdminServiceProvider.php`
- `packages/Webkul/Admin/src/Http/Middleware/Bouncer.php`
- `packages/Webkul/Admin/src/Bouncer.php`
- `packages/Webkul/Admin/src/Config/acl.php`
- `packages/Webkul/Admin/src/Config/menu.php`
- `config/app.php`

Krayin admin routes are registered with middleware `web`, `admin_locale`, and `user`; frontend routes use `web` and `admin_locale`. The admin prefix is configurable by `APP_ADMIN_PATH` and defaults to `admin`. Never hardcode `/admin`.

The Bouncer middleware:

- requires the `user` guard;
- rejects inactive users;
- rejects users with missing/empty role permissions;
- authorizes mapped routes using route-name ACLs;
- fails closed for unmapped privileged route names.

**Integration boundary:** Do not attach Krayin's Bouncer middleware to Statamic Control Panel routes by default. Statamic must use its own CP authorization pipeline. Add a specific integration check for the Krayin account's active `status` before authenticated Statamic CP requests, because Krayin's Bouncer does not run on Statamic's route group automatically.

## 4. Lead and contact intake

Source files inspected:

- `packages/Webkul/Lead/src/Repositories/LeadRepository.php`
- `packages/Webkul/Lead/src/Models/Lead.php`
- `packages/Webkul/Contact/src/Repositories/PersonRepository.php`
- `packages/Webkul/WebForm/src/Http/Controllers/WebFormController.php`
- `packages/Webkul/WebForm/src/Http/Requests/WebForm.php`
- `packages/Webkul/WebForm/src/Repositories/WebFormRepository.php`
- `packages/Webkul/WebForm/src/Routes/routes.php`

Krayin already contains a WebForm package and public `/web-forms` submission route. Its controller:

- restricts submissions to the attributes configured for the form;
- finds an existing person by exact case-insensitive email matching;
- creates or reuses the person through `PersonRepository`;
- creates a lead through `LeadRepository`;
- applies the form's configured/default pipeline and first stage;
- resolves the lead source and type;
- dispatches lead create events.

The controller mutates the global HTTP request and is not a reusable domain service. Do not call it internally from Statamic or make a local HTTP request to the Krayin form endpoint.

**Recommended integration:** create a service-level intake adapter that accepts an explicit validated payload from a Statamic form submission and calls the existing CRM repositories/business logic. Preserve duplicate/contact handling, pipeline/source/type defaults, ownership rules and lead events. Add an idempotency key and processing log so retries cannot create duplicate leads.

## 5. Source-specific implementation rules

1. Keep Krayin's `user` guard, user model, role assignment and Bouncer behaviour intact.
2. Keep Krayin authoritative for employee creation, credential management, account status and CRM role assignment.
3. Keep Statamic CMS permissions independent of Krayin CRM permissions.
4. Never let Statamic's native user creation screen create Krayin users without Krayin's required `role_id` and account lifecycle.
5. Respect `APP_ADMIN_PATH` in links and tests.
6. Do not duplicate Krayin leads, contacts, organizations, or Statamic content models.
7. Reuse repositories/domain services, not controllers or request-mutating HTTP handlers.
8. Runtime compatibility is still pending; this source audit does not demonstrate Composer resolution, Laravel boot, or working Statamic authentication.

## 6. Files and methods to verify during runtime

- `config/auth.php`: confirm Statamic's selected CP guard resolves Krayin's `user` provider.
- `Webkul\\User\\Models\\User`: test Statamic's Eloquent wrapping with the actual model and schema.
- `AdminServiceProvider`: ensure route groups remain isolated.
- `Bouncer::handle()`: ensure inactive Krayin accounts are also rejected by the Statamic CP integration middleware.
- `LeadRepository::create()`: verify a Statamic-submitted lead receives valid person, owner, pipeline, stage, source, type and custom-attribute data.
- `WebFormController::formStore()`: compare the new service adapter against existing public form validation and deduplication rules.

**Conclusion:** Krayin offers clear source-level extension points for shared identity and lead creation. The combination remains subject to the runtime compatibility gates in `COMPATIBILITY-PLAN.md`.