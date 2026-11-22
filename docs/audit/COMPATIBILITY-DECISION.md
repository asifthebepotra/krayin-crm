# SA-01 Compatibility Decision

**Decision status:** PENDING — runtime verification not yet executed
**Krayin:** v2.2.8 (`ed6deebfcb8c616eaf352c893a10a6d8e666fc6e`)
**Statamic:** v6.42.0 (`3b28f8ceda4e0912ffe51dc7d8d877f0184f23bc`)
**Laravel in baseline lock:** v12.71.0
**Target PHP platform:** 8.3.30

## Evidence available

- The Krayin source tag and user's branch baseline resolve to the same immutable commit.
- The Statamic v6.42.0 tag resolves to its recorded immutable commit.
- Krayin declares Laravel ^12.0 and PHP ^8.3.
- Statamic declares Laravel ^12.40.0 || ^13.0.
- Current Krayin lock pins Laravel 12.71.0 and Symfony HttpFoundation 7.4.23, satisfying the overlapping declared constraints inspected so far.
- Krayin's authentication uses the `user` session guard and `Webkul\\User\\Models\\User`.
- Krayin's Bouncer middleware checks route-name ACL and fails closed for unmapped privileged routes.
- Krayin contains an existing WebForm-to-lead workflow, but its controller is request-dependent and should not be invoked internally from Statamic.

## Not yet verified

- Combined Composer dependency resolution.
- Statamic Control Panel boot and routes in the Krayin application.
- Inertia version selected by Composer and runtime support.
- Statamic authentication against/adapted to the Krayin User model.
- Permission enforcement and disabled-user access revocation.
- Statamic database migrations and flat-file content persistence.
- Public website rendering and cache invalidation.
- CRM regression tests after Statamic installation.
- Production Statamic edition/licensing entitlement.

## Decision

**Do not start the custom integration package yet.** The declared dependency intersection is encouraging but not a runtime compatibility result. SA-01 remains open until all mandatory tests in COMPATIBILITY-PLAN.md pass.

## Approval record

| Gate | Result | Evidence |
|---|---|---|
| Source pins | PASS | Release tag and commit checks |
| Dependency constraints | PRELIMINARY PASS | Composer manifests and existing lock |
| Composer solver | PENDING | Dry run and actual install required |
| Laravel boot/routes | PENDING | Integrated runtime tests required |
| Database/content/storage | PENDING | Integrated runtime tests required |
| Authentication/ACL | PENDING | Prototype and negative authorization tests required |
| Public rendering | PENDING | Published page smoke test required |
| CRM regression | PENDING | Baseline comparison required |
| Licensing | PENDING | Edition and commercial terms confirmation required |

Final status can be changed to GO only after evidence is attached for every mandatory gate. A source inspection or a successful Composer dry run alone is insufficient.



## Additional source-level conclusion

A source-supported shared-login candidate now exists: set Statamic's Control Panel guard to Krayin's `user` guard and configure Statamic's Eloquent user repository to resolve the model from that guard's provider. Statamic v6.42.0's Eloquent user repository explicitly wraps arbitrary Laravel Eloquent models, and its CP login flow uses the configured current guard.

This still requires a runtime prototype. The major blockers to verify are:
- Statamic CMS role/group pivots need their own table names and migrations, separate from Krayin's role/group structures.
- Krayin's `users` schema lacks Statamic preference, last-login and two-factor columns accessed by the Statamic Eloquent adapter.
- Krayin's `status` enforcement is not automatically applied to Statamic CP routes; a dedicated active-user middleware is required.
- Statamic user creation must be disabled/restricted because it writes directly to the configured Eloquent user model and could bypass Krayin's required `role_id`.
- Theme ZIP installation and active website theme resolution are custom features; Statamic's deprecated Theme tag is only an asset helper, not a full website theme manager.

These are design candidates and implementation constraints, not a GO decision. Composer/runtime gates remain pending.


## Expanded source-audit findings (2026-11-22)

A deeper source inspection supports a more specific shared-login prototype:

- Statamic v6.42's Eloquent user repository resolves its Eloquent model from the selected Statamic CP guard's Laravel provider and can wrap generic Eloquent models.
- Krayin's `user` guard and `Webkul\\User\\Models\\User` are therefore a plausible shared identity path.
- Statamic's CP authorization is independent of Krayin's Bouncer. CMS role permissions must remain separate from CRM permissions.
- Krayin's `users.status` enforcement must be added to the authenticated Statamic CP path; the Krayin Bouncer middleware is not automatically applied there.
- Statamic Eloquent user functions expect `preferences` and `last_login`; the inspected Krayin user schema lacks them. Add reviewed integration migrations/model casts or disable the relevant features.
- Statamic native user creation must be blocked/restricted because it writes to the configured Eloquent users model and could bypass Krayin's required `role_id` and account lifecycle.
- Krayin already has WebForm intake; a Statamic form adapter should call a domain/service-level lead intake flow, not the request-mutating WebForm controller.
- Statamic public rendering goes through Laravel's view finder and Antlers engine. ZIP themes require a custom, request-safe template resolver and controlled asset path; the old Statamic Theme tag is not a full theme manager.

## New feasibility documents

- `AUTHENTICATION-AND-ACL-FEASIBILITY.md`
- `TEMPLATE-AND-THEME-FEASIBILITY.md`

## Decision remains PENDING

These findings strengthen the architecture proposal but do not change the compatibility decision to GO. No Composer solver run, PHP application boot, CP login, permission test, database migration test, public rendering test or Krayin post-install regression test has yet been executed.

## Additional mandatory blocker: root homepage route

Source inspection found that Krayin's `GET /` route redirects to the admin login, while Statamic's public frontend is registered as an optional catch-all. Runtime route ordering and a deliberate Statamic root-route override have not yet been tested.

The intended production result is that the domain root serves the Statamic homepage and Krayin login remains below the configurable admin path. This is now a mandatory acceptance test.

This finding does not change the status: **PENDING — no runtime GO decision yet.**



## Additional route/API scope decision

The Statamic Content API is disabled by default and is not required for the native-Control-Panel plus server-rendered-website MVP. Keep it disabled during SA-01. If enabled in a later phase, verify its default `api` prefix does not collide with Krayin's existing API route group and review every exposed resource.


## Local Composer dry-run update (2026-11-22)

**Dependency solver: PASS (dry-run only).** User-provided terminal output shows 35 installs, 12 updates and zero removals. Composer selected Statamic v6.42.0, Inertia Laravel v2.0.28 and Laravel v12.73.0 (up from v12.71.0). Guzzle is proposed to move from 7.16.1 to 7.16.5; Symfony HttpFoundation remains v7.4.23.

The local `php artisan config:clear` attempt failed because `vendor/autoload.php` was absent in the fresh checkout. No runtime compatibility result is implied.

The Composer validate warnings about the exact Statamic version and stale lockfile are expected for this pinned audit plus `--no-update` stage. Actual installation, Laravel boot, route tests, auth/ACL tests, rendering and regression tests remain pending. The overall decision remains **PENDING**, not GO.
