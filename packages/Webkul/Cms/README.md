# Krayin Statamic CMS Module

The package adds Statamic CMS to Krayin CRM as a first-party module. Statamic remains the content engine and native Control Panel; Krayin remains the CRM and employee-account system.

## Current module features

- CMS entry in the Krayin admin sidebar.
- CMS overview linked to the native Statamic Control Panel.
- Website settings: name, primary domain, default locale and timezone.
- CMS access management for existing active Krayin employees.
- Separate Statamic CMS roles: CMS Administrator, Content Editor and Content Author.
- Shared Laravel `user` guard candidate, with Statamic-specific user casts supplied by a child model.
- Separate CMS role/group pivot tables.
- Middleware that rejects inactive Krayin employees from authenticated Statamic Control Panel routes.
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
| Theme ZIP installation/activation | Next module increment |
| Form submissions to Krayin leads | Next module increment |

The Statamic native user-management permission set is deliberately not included in the bundled CMS roles. Employee provisioning and password management remain in Krayin.

## Initial installation on a new checkout

1. Install the root Composer dependencies with the Statamic version pinned in the root `composer.json`.
2. Run Laravel package discovery.
3. Run Statamic's install command.
4. Run the Krayin migrations, including the CMS module migrations.
5. Run `php artisan cms:install` to merge the bundled CMS roles into `resources/users/roles.yaml`.
6. Assign CMS roles from **Krayin → CMS → CMS Access**.

For an initial Composer dependency update where `vendor/` does not exist yet, use `--no-scripts` for the targeted update and then run package discovery and Statamic installation manually. Subsequent Composer updates use the Statamic scripts configured at the application root.

## Route entry points

- Krayin module: `/{APP_ADMIN_PATH}/cms`
- Website settings: `/{APP_ADMIN_PATH}/cms/settings`
- CMS access: `/{APP_ADMIN_PATH}/cms/users`
- Statamic Control Panel: configured `statamic.cp.route`, default `/cp`
- Public homepage: `/` is rendered by Statamic when Statamic frontend routes are enabled.

## Important implementation notes

- The package does not replace the Statamic editor or create duplicate page/blog/media tables.
- Statamic role and group definitions use file storage initially; their employee assignment pivots use `statamic_role_user` and `statamic_group_user`.
- The CMS user model extends Krayin's User model and keeps the existing `users` table.
- Run the integration migration before assigning CMS roles.
- Test the shared session and role permissions locally before production deployment.
