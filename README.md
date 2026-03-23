# ABC Suite

ABC Suite is a deployable WordPress plugin for ABC Printing's internal workflow.

## Current shape

This repository contains one unified plugin entrypoint:

- `plugin.php` — WordPress plugin bootstrap
- `includes/` — module classes and runtime logic
- `assets/` — JS/CSS/images
- `templates/` — admin/frontend templates
- `tools/` — local helper scripts and non-runtime tooling

Legacy shim files remain in place for compatibility:

- `abc-b2b-designer.php`
- `estimator-plugin.php`

They intentionally forward to `plugin.php` and are not standalone plugin roots.

## Packaging for deployment

Do not ship business artifacts or old ZIP exports inside the production plugin package.

Use `.distignore` as the exclusion list for packaging. At minimum exclude:

- `*.zip`
- `*.pdf`
- `*.xlsx`
- `*.csv`
- `tools/`
- `.git/`

## Runtime hardening added

The plugin bootstrap now:

- loads modules through a central manifest (`includes/class-module-loader.php`)
- reports missing module files/classes in wp-admin notices instead of failing silently
- records per-module registration failures so debugging is less miserable

## Recommended next refactor

1. Split `includes/` into `Core/` and `Modules/`
2. Give each module its own bootstrap/service class
3. Move AJAX handlers into module-local files
4. Add a tiny logger + diagnostics page
5. Add a repeatable local WordPress test environment with debug logging enabled

See also:

- `ARCHITECTURE.md` — target plugin architecture and domain model
- `ROADMAP.md` — phased implementation plan

## Debugging

When WordPress fatals, the most useful file is usually:

- `wp-content/debug.log`

Recommended wp-config settings during development:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```
