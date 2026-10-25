# Upgrade Guide

## Access

`composer update` reads the private repository over SSH: a developer's own key, or the server's read-only deploy key. Setup and the `~/.ssh/config` alias for servers: [README.md → Access](README.md#access). Never commit `auth.json`.

## Standard steps (every upgrade)

Run in the project root:

1. `composer update yuan/admin-core`
2. Regenerate types and routes: `php artisan typescript:transform && php artisan wayfinder:generate --with-form` (the skeleton's `post-update-cmd` already runs both, plus `php artisan boost:update --no-discover` for the AI guideline and skills), then commit the regenerated `resources/js/types/generated.d.ts`, `resources/js/actions`, `resources/js/routes`, `resources/js/wayfinder`, `CLAUDE.md` and `.claude/skills`.
3. `npm run typecheck`: fix any TS error caused by a changed core Data class.
4. `php artisan migrate && php artisan permissions:sync && php artisan optimize`
5. `composer check`

Version-specific steps are listed below, newest first.

## 1.0.0

First release. Nothing to do.
