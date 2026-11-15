# SA-01 — Krayin + Statamic Source Audit

**Scope:** Single Laravel application using Krayin CRM v2.2.8 and Statamic CMS v6.42.0.

## Pinned source releases

- Krayin CRM v2.2.8 — `ed6deebfcb8c616eaf352c893a10a6d8e666fc6e`
- Statamic CMS v6.42.0 — `3b28f8ceda4e0912ffe51dc7d8d877f0184f23bc`
- Existing Krayin lock resolves Laravel v12.71.0 and PHP platform 8.3.30.

## Audit documents

1. [Krayin source audit](KRAYIN-SOURCE-AUDIT.md) — actual repository structure, user guard, Bouncer, lead repository and WebForm source findings.
2. [Statamic source audit](STATAMIC-SOURCE-AUDIT.md) — selected Statamic package manifest, dependencies, package discovery, authentication questions and licensing.
3. [Compatibility plan](COMPATIBILITY-PLAN.md) — exact release pins, execution gates, test checklist and go/no-go criteria.
4. [Compatibility decision](COMPATIBILITY-DECISION.md) — current decision record. It remains PENDING until runtime tests are executed.

## Current status

- Source inspection: complete for the documented files.
- Declared Composer constraint intersection: preliminary pass.
- Combined Composer install and solver: not run.
- Laravel boot and route coexistence: not run.
- Authentication/permission prototype: not run.
- Database, content and media persistence: not run.
- Public Statamic rendering: not run.
- Krayin regression suite after installation: not run.

## Implementation gate

Do not begin the custom `Webkul/StatamicIntegration` package until all mandatory runtime checks pass and `COMPATIBILITY-DECISION.md` records GO with evidence.

GitHub source inspection does not execute PHP, Composer, MySQL or Laravel. The runtime tests must be performed in a PHP 8.3 development environment with the pinned commits.