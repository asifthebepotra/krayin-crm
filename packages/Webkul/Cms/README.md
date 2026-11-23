# Krayin Statamic CMS Module

The package adds Statamic CMS to Krayin CRM as a first-party module. Statamic remains the content engine and native Control Panel; Krayin remains the CRM and employee-account system.

## Included module features

- CMS entry and sub-navigation inside the Krayin admin sidebar.
- CMS overview linked to the native Statamic Control Panel.
- Website settings: name, primary domain, default locale and timezone.
- Default owner for incoming website leads.
- CMS access management for existing active Krayin employees.
- Separate Statamic CMS roles: CMS Administrator, Content Editor and Content Author.
- Shared Laravel `user` guard integration candidate, reusing the existing Krayin employee table.
- Separate Statamic role/group assignment pivot tables.
- Middleware that rejects inactive Krayin employees from authenticated Statamic Control Panel routes.
- Statamic Pages collection with a published starter homepage, blog landing page and contact page.
- Statamic Blog collection and article blueprint.
- Main navigation, site global and public asset container.
- Native Statamic contact form connected to Krayin leads.
- Exact-email contact matching and idempotent submission processing.
- Website Enquiries screen with status, totals and links to CRM leads.
- ZIP theme installer, versioned theme storage, activation/rollback by version and removal of inactive themes.
- Public-only, request-scoped theme view resolution and a `cms_theme:asset` Antlers tag.
- Developer starter theme package under `examples/themes/starter/`.
- Statamic public homepage route configured to take precedence over Krayin's legacy login redirect at `/`.

## Ownership

| Data / feature | Owner |
|---|---|
| Employee accounts, passwords and active status | Krayin |
| CRM roles and CRM permissions | Krayin |
| CMS roles and content permissions | Statamic |
| CMS role assignments to existing employees | Krayin CMS module |
| Pages, collections, blueprints and entries | Statamic |
| Blog content and publishing | Statamic |
| Assets, navigation and form submissions | Statamic |
| Website identity/default locale/domain metadata | Krayin CMS module |
| Enquiry-to-lead processing | Krayin CMS integration service |
| Theme ZIP packages and active version | Krayin CMS module |

The Statamic native user-management permissions are deliberately excluded from the bundled CMS roles. Employee provisioning, passwords and CRM roles remain managed by Krayin.

## First installation on the feature branch

1. Install the root Composer dependencies with Statamic v6.42.0 and Inertia Laravel v2 pinned in the root `composer.json`.
2. For the first targeted dependency update on a fresh checkout, use `--no-scripts`; then run `php artisan package:discover --ansi` and `php artisan statamic:install --ansi` manually.
3. Run `php artisan migrate` to create Krayin and CMS integration tables.
4. Run `php artisan cms:install` to copy the starter pages, blog, contact form, blueprints, views and CMS roles. It copies only missing files and does not overwrite existing website content.
5. Open **Krayin → CMS → Website Settings** and choose an active default lead owner.
6. Assign employee CMS roles from **Krayin → CMS → CMS Access**.
7. Edit and publish content through Statamic's native Control Panel.
8. Install theme ZIPs from **Krayin → CMS → Themes**.

Statamic Pro licensing and multi-user entitlements must be confirmed before production deployment.

## Route entry points

- Krayin module: `/{APP_ADMIN_PATH}/cms`
- Website settings: `/{APP_ADMIN_PATH}/cms/settings`
- CMS access: `/{APP_ADMIN_PATH}/cms/users`
- Website enquiries: `/{APP_ADMIN_PATH}/cms/submissions`
- Theme manager: `/{APP_ADMIN_PATH}/cms/themes`
- Statamic Control Panel: configured `statamic.cp.route`, default `/cp`
- Public homepage: `/` is rendered by Statamic when Statamic frontend routes are enabled.

## Website enquiry flow

1. Visitor submits the Statamic `contact` form.
2. Statamic validates and stores the form submission.
3. The integration normalizes only the allowed contact fields.
4. Existing persons are matched by exact email address, case-insensitively; otherwise Krayin's `LeadRepository` creates the person.
5. A Krayin lead is created using the default pipeline, first stage, the configured active owner, the `Web Form` source when available (otherwise the first source), and the first lead type.
6. The integration stores the result in `cms_submission_logs`, with a unique form handle and Statamic submission ID to prevent duplicate leads on retries.
7. Administrators review results under **Krayin → CMS → Website Enquiries**.

## Theme ZIP format

Build a ZIP from the contents of `examples/themes/starter/`, so the ZIP root contains `theme.json`, `views/` and optional `assets/`.

Required:
- `theme.json` with `name`, `slug`, `version` and `minimum_statamic_version`.
- `views/layout.antlers.html`.
- `views/default.antlers.html`.

Allowed assets are CSS, JavaScript, common raster images and font files. The installer rejects server-side PHP, Composer files, route/provider files, hidden paths, traversal, symlinks and special filesystem entries. JavaScript is client-side theme code and should be reviewed as trusted theme content before installation.

Use the Antlers tag to reference versioned theme assets:

```antlers
<link rel="stylesheet" href="{{ cms_theme:asset path='assets/css/app.css' }}">
```

## Important implementation notes

- The package does not replace the Statamic editor or create duplicate page/blog/media tables.
- Statamic role and group definitions use file storage initially; employee assignment pivots use `statamic_role_user` and `statamic_group_user`.
- `CmsUser` extends Krayin's existing User model; the employee identity and password remain in the existing `users` table.
- Run the CMS integration migrations before assigning CMS roles, processing website enquiries or installing themes.
- Website lead creation requires an active default lead owner in Website Settings.
- Runtime verification on the local PHP/MySQL environment is still required before production use. In particular, test shared login/logout, permissions, public root routing, form processing and theme activation on the pinned dependency set.
