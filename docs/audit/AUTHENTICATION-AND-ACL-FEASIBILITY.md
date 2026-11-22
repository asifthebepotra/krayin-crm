# SA-01 Feasibility: Shared Authentication and CMS ACL

**Selected releases:** Krayin CRM v2.2.8 and Statamic CMS v6.42.0  
**Status:** Source-supported candidate; runtime prototype required  
**Scope:** One Laravel application, one company, one shared employee identity.

## 1. Finding: Statamic can wrap an existing Eloquent user model

The pinned Statamic source provides a direct route to reuse an existing Laravel application user:

- `Statamic\\Auth\\UserRepositoryManager::createEloquentDriver()` resolves the Eloquent model from the guard configured in `statamic.users.guards.cp`, then follows Laravel's guard provider to its model.
- `Statamic\\Auth\\Eloquent\\UserRepository::fromUser()` supports an existing Statamic user contract, an Eloquent model exposing `toStatamicUser()`, or a generic Eloquent model. For a generic Eloquent model it returns Statamic's Eloquent User wrapper around that model.
- Statamic's CP `AuthGuard` calls `Auth::shouldUse(config('statamic.users.guards.cp'))`.
- Statamic's login controller logs into that configured Laravel guard; Laravel's session guard stores the user identifier. On the next request, the Krayin Eloquent provider can resolve the Krayin user record and Statamic can wrap it again.

Relevant pinned source:
- [Statamic Eloquent UserRepository](https://github.com/statamic/cms/blob/v6.42.0/src/Auth/Eloquent/UserRepository.php)
- [Statamic UserRepositoryManager](https://github.com/statamic/cms/blob/v6.42.0/src/Auth/UserRepositoryManager.php)
- [Statamic CP AuthGuard](https://github.com/statamic/cms/blob/v6.42.0/src/Http/Middleware/CP/AuthGuard.php)
- [Statamic CP Authorize](https://github.com/statamic/cms/blob/v6.42.0/src/Http/Middleware/CP/Authorize.php)
- [Krayin auth configuration](https://github.com/asifthebepotra/krayin-crm/blob/2.2/config/auth.php)
- [Krayin User model](https://github.com/asifthebepotra/krayin-crm/blob/2.2/packages/Webkul/User/src/Models/User.php)

### Proposed configuration candidate

The intended runtime prototype is:

- Krayin's existing Laravel guard remains named `user`.
- `auth.guards.user` remains a session guard backed by the existing `users` provider.
- `auth.providers.users.model` remains `Webkul\\User\\Models\\User`.
- Statamic user repository is `eloquent`.
- Statamic CP guard is `user`.
- Statamic frontend guard is explicitly configured and tested; for a unified identity it can also be `user`, provided that the application's frontend requests and any protected routes behave correctly.

This must be implemented through Statamic's supported configuration, not by editing Statamic core classes.

## 2. Keep identity, CRM permissions and CMS permissions separate

| Concern | System of record |
|---|---|
| Employee account creation/deactivation | Krayin |
| Email/password and CRM account lifecycle | Krayin |
| CRM role and CRM data-scope permissions | Krayin |
| CMS roles and CMS content permissions | Statamic |
| Authentication session | Shared Laravel session using Krayin's `user` guard |
| User-to-CMS-role relationship | Statamic's own configured role pivot |

Do not translate Krayin role permission strings directly into Statamic permissions. Their permission models have different semantics.

Statamic's authorization Gate converts the authenticated Laravel user through `Statamic\\Facades\\User::fromUser()` and asks the wrapped Statamic user to evaluate Statamic permissions. Krayin's Bouncer separately checks Krayin route names and CRM permissions.

## 3. Avoid table collisions

Krayin already owns tables named `users`, `roles`, `groups` and `user_groups`. Krayin's users table contains a required `role_id` foreign key to its own roles table.

Statamic v6.42 defaults include:
- user table: `users`
- role pivot: `role_user`
- roles table: disabled/file-backed by default
- group pivot: `group_user`
- groups table: disabled/file-backed by default

Recommended initial Statamic configuration:

- Keep Statamic roles and groups file-backed.
- Reuse the Krayin `users` table only as the identity model.
- Configure distinct CMS pivots, e.g. `statamic_role_user` and `statamic_group_user`.
- Do not configure Statamic to use Krayin's `roles`, `groups` or `user_groups` tables.
- Review `php please auth:migration` output before running it; ensure the generated migrations use the configured table names and do not overwrite existing Krayin schema.

## 4. Krayin user fields Statamic expects

The actual Krayin user migration defines `id`, `name`, `email`, nullable `password`, `status`, `role_id`, `remember_token`, and timestamps.

Statamic's Eloquent adapter reads/writes:
- `preferences` for Control Panel preferences.
- `last_login` for the user's last login timestamp.

These columns are absent from the inspected Krayin user migration. Add nullable `preferences` (JSON) and `last_login` (timestamp) with an integration-owned migration, and add suitable casts to the Krayin User model. Verify with migration review and runtime tests.

Statamic's 2FA implementation expects additional two-factor fields. Disable Statamic 2FA for the initial compatibility prototype/release. If it is enabled later, add the required fields through a reviewed integration migration and test recovery-code storage and encryption.

## 5. Krayin account status must be enforced in the Statamic CP

Krayin's Bouncer middleware verifies `users.status`, but it is registered only on Krayin admin routes. Statamic uses its own `statamic.cp.authenticated` middleware group and its own `Authorize` middleware.

Therefore the shared-auth design needs a narrow integration middleware in the Statamic authenticated CP pipeline that:

1. Resolves the authenticated user through Krayin's `user` guard.
2. Verifies that the user exists and `status` is active.
3. Rejects inactive users.
4. Logs out the Krayin guard and invalidates/regenerates the shared session when the account is disabled.
5. Runs before any privileged CMS action.

Do not assume the Statamic user wrapper's `status()` method handles this; the inspected Statamic Eloquent wrapper has a TODO implementation for that method.

## 6. Restrict Statamic user administration

Statamic's native CP UsersController creates users through the configured Statamic Eloquent user repository. With Krayin's users table, that would attempt to create a Krayin user without necessarily supplying Krayin's required `role_id` and without following Krayin's account provisioning lifecycle.

For the first release:

- Krayin remains the only place to create, deactivate and delete employees.
- Do not grant Statamic's native `create users`, `delete users` or `change passwords` permissions.
- Do not allow Statamic to modify Krayin email, name, account status, password or CRM role.
- Keep the Statamic user-management screen hidden/disabled unless an explicitly safe read-only or CMS-role-only workflow is added.
- CMS role assignment, if exposed, must modify only the CMS role pivot and must not modify Krayin's `role_id`.

Statamic's built-in Control Panel and content editing remain native. The integration does not need to recreate the editor.

## 7. Password resets, logout and session handling

The Krayin and Statamic Control Panels will share a Laravel session. Both systems' logout flows invalidate the session; therefore logging out of one should log out of the other. Verify this is acceptable and consistent in the user experience.

Statamic's official independent-guard documentation warns that it updates `last_login` for Eloquent users and that invalidating the shared session signs the user out of both applications.

For the first release, keep Krayin as the owner of employee password changes and resets. Do not let Statamic's password-reset/activation workflows create a second identity lifecycle. Review Statamic password broker settings and route permissions before enabling its reset UI.

Official Statamic references:
- [Existing Laravel application installation](https://statamic.dev/getting-started/installing/laravel)
- [Using an independent authentication guard](https://statamic.dev/knowledge-base/tips/using-an-independent-authentication-guard)
- [Storing users in a database](https://statamic.dev/knowledge-base/tips/storing-users-in-a-database)

## 8. Runtime prototype and acceptance tests

This is a candidate, not a passed compatibility result. Prototype it in the audit branch after Composer installation.

1. Sign in to Krayin through `/admin/login` (or the configured `APP_ADMIN_PATH`).
2. Open the Statamic Control Panel in the same browser without a second password prompt.
3. Confirm `Statamic::User::current()->id()` equals the authenticated Krayin user ID.
4. Confirm a CMS role grants `access cp`.
5. Confirm a user with no CMS access is denied even if they have CRM access.
6. Confirm CMS permissions do not grant Krayin CRM ACL permissions.
7. Confirm logout from Statamic invalidates the shared session and Krayin login is also cleared.
8. Confirm Krayin logout invalidates the shared session and Statamic CP login is also cleared.
9. Disable the Krayin account; verify the next authenticated Statamic CP request rejects and invalidates the session.
10. Confirm Statamic user creation is unavailable and cannot create a Krayin user missing `role_id`.
11. Confirm no Statamic account edit can change Krayin's name, email, password, account status or CRM role.
12. Confirm CMS role assignment affects only the Statamic pivot.

**Decision:** proceed with the shared guard + Statamic Eloquent wrapper prototype; do not build a separate identity mapping table unless the runtime tests reveal a concrete incompatibility.