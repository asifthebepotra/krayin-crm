# Krayin Statamic CMS Module

The package adds Statamic CMS to Krayin CRM as a first-party module. Statamic remains the content engine and native Control Panel; Krayin remains the CRM and employee-account system.

## Current module features

- CMS entry in the Krayin admin sidebar.
- CMS overview linked to the native Statamic Control Panel.
- Website settings: name, primary domain, default locale and timezone.
- Default owner for incoming website leads.
- CMS access management for existing active Krayin employees.
- Separate Statamic CMS roles: CMS Administrator, Content Editor and Content Author.
- Shared Laravel `user` guard candidate, with Statamic-specific user casts supplied by a child model.
- Separate CMS role/group pivot tables.
- Middleware that rejects inactive Krayin employees from authenticated Statamic Control Panel routes.
- Statamic Pages collection with a published starter homepage, blog landing page and contact page.
- Statamic Blog collection and article blueprint.
- Main navigation and public asset container.
- Native Statamic contact form connected to Krayin leads.
- Exact-email contact matching and submission idempotency.
- Website Enquiries dashboard with processing status and links to CRM leads.
- Statamic public homepage route takes precedence over Krayin's legacy login redirect at `/`.

## Ownership

| Data / feature | Owner |
|---|---|
| Employee accounts, password, active state | Krayin |
| CRM roles and CRM permissions | Krayin |
| CMS roles and content permissions | Statamic |
| CMS role assignments to existing employees | Krayin CMS module |
| Pages, collections, blueprints and entries | Statamic |
| Blog content and publishing | Statamic |
| Assets and navigation | Statamic |
| Website identity/default locale/domain metadata | Krayin CMS module |
| Website form submissions | Statamic |
| Enquiry-to-lead processing | Krayin CMS integration service |
| Theme ZIP installation/activation | Next module increment |

The Statamic native user-management permission set is deliberately not included in the bundled CMS roles. Employee provisioning, passwords and CRM roles remain managed by Krayin.

## First installation

1. Install the root Composer dependencies with Statamic v6.42.0 and Inertia Laravel v2 pinned in the root `composer.json`.
2. Run Laravel package discovery.
3. Run Statamic's install command.
4. Run the Krayin migrations, including the CMS module migrations.
5. Run `php artisan cms:install` to merge the starter pages, blog, contact form, blueprints, views and CMS roles. Existing files are never overwritten.
6. Open **Krayin → CMS → Website Settings** and select an active default lead owner.
7. Assign CMS roles from **Krayin → CMS → CMS Access**.
8. Open Statamic's Control Panel to edit pages and publish content.

For a new checkout without `vendor/`, use the approved targeted Composer update with `--no-scripts`, then run package discovery and Statamic installation manually. Subsequent Composer operations use the Statamic scripts configured at the application root.

## Route entry points

- Krayin module: `/{APP_ADMIN_PATH}/cms`
- Website settings: `/{APP_ADMIN_PATH}/cms/settings`
- CMS access: `/{APP_ADMIN_PATH}/cms/users`
- Website enquiries: `/{APP_ADMIN_PATH}/cms/submissions`
- Statamic Control Panel: configured `statamic.cp.route`, default `/cp`
- Public homepage: `/` is rendered by Statamic when Statamic frontend routes are enabled.

## Website enquiry flow

1. Visitor submits the Statamic `contact` form.
2. Statamic validates and processes the form fields.
3. The integration listener normalizes the allowed contact fields.
4. Existing people are matched by exact email address, case-insensitively; otherwise a person is created by Krayin's LeadRepository flow.
5. A lead is created using Krayin's default pipeline, first stage, a configured active owner, the `Web Form` source when available (otherwise the first source), and the first lead type.
6. The integration records success/failure in `cms_submission_logs`, keyed uniquely by Statamic form handle and submission ID.
7. Administrators review results under **Krayin → CMS → Website Enquiries**.

## Important implementation notes

- The package does not replace the Statamic editor or create duplicate page/blog/media tables.
- Statamic role and group definitions use file storage initially; employee assignment pivots use `statamic_role_user` and `statamic_group_user`.
- The CMS user model extends Krayin's User model and keeps the existing `users` table.
- Run the CMS module migrations before `php artisan cms:install`.
- Website lead creation remains disabled in practice until an active default lead owner is selected in Website Settings.
- The theme ZIP manager and active theme view resolver are the next implementation increment.
- Runtime verification on a local PHP/MySQL environment is still required before production use.
