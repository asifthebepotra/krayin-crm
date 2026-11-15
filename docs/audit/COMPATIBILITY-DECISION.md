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