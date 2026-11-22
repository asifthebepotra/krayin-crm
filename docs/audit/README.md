# SA-01 — Krayin + Statamic Source Audit

**Scope:** One Laravel application using Krayin CRM v2.2.8 and Statamic CMS v6.42.0.  
**Audit status:** Source audit expanded; runtime compatibility tests pending.

## Pinned source releases

- Krayin CRM v2.2.8 — `ed6deebfcb8c616eaf352c893a10a6d8e666fc6e`
- Statamic CMS v6.42.0 — `3b28f8ceda4e0912ffe51dc7d8d877f0184f23bc`
- Existing Krayin lock resolves Laravel v12.71.0 and PHP platform 8.3.30.

## Audit documents

1. [Krayin source audit](KRAYIN-SOURCE-AUDIT.md) — actual repository structure, user schema, guard, Bouncer, lead repository and WebForm workflow.
2. [Statamic source audit](STATAMIC-SOURCE-AUDIT.md) — exact package release, Composer constraints, provider discovery, licensing and installation notes.
3. [Authentication and ACL feasibility](AUTHENTICATION-AND-ACL-FEASIBILITY.md) — source-backed shared guard candidate, independent CMS roles, missing user fields, account lifecycle safeguards and runtime acceptance tests.
4. [Template and ZIP theme feasibility](TEMPLATE-AND-THEME-FEASIBILITY.md) — Statamic's native rendering path, custom theme manager boundary, archive security, active theme resolution and acceptance tests.
5. [Compatibility plan](COMPATIBILITY-PLAN.md) — exact release pins, Composer sequence, runtime gates and go/no-go criteria.
6. [Compatibility decision](COMPATIBILITY-DECISION.md) — current decision record; remains PENDING until runtime tests pass.

## Confirmed source findings

- Krayin's primary session guard is `user`, backed by `Webkul\\User\\Models\\User`.
- Krayin has a route-name Bouncer middleware and a separate CRM permission model.
- Krayin's user schema has a required `role_id` and does not initially include Statamic's `preferences` and `last_login` fields.
- Krayin already has a WebForm-to-lead flow; reuse its CRM business logic through a service adapter, not by calling the request-mutating controller.
- Statamic v6.42's Eloquent user repository can wrap generic Eloquent models; a shared guard is a viable candidate, subject to runtime proof.
- Statamic CMS roles and CRM roles must remain separate.
- Statamic's native user creation cannot be allowed to create Krayin employees outside Krayin's lifecycle.
- Statamic's native renderer uses Laravel views/Antlers; ZIP theme management and active theme resolution are custom integration features.

## Current status

- Source inspection: completed for the documented paths.
- Declared Composer constraint intersection: preliminary pass.
- Composer solver/actual install: not run.
- Laravel boot and route coexistence: not run.
- Shared guard/permissions prototype: not run.
- Database/content/media persistence: not run.
- Public rendering and ZIP theme prototype: not run.
- Krayin regression suite after installation: not run.

## Implementation gate

Do not begin `Webkul/StatamicIntegration` until the mandatory runtime checks pass and `COMPATIBILITY-DECISION.md` records GO with evidence.

GitHub source inspection does not execute PHP, Composer, MySQL or Laravel. Runtime tests must be run in the local PHP development environment using the immutable source pins.