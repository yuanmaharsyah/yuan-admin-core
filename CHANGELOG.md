# Changelog

All notable changes to `yuan/admin-core` are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the package uses [SemVer](https://semver.org/).

## [Unreleased]

### Added
- Package scaffold: Testbench test suite on in-memory SQLite, Pest 5 with arch hygiene rules, Larastan level 8 (no baseline) and Pint with strict types.
- Package auto-discovery (`AdminCoreServiceProvider`) and `config/admin-core.php`.
- Module registry (`Yuan\AdminCore\Registry`): discovers the core and project module roots, validates every `module.php` (spec §3.1), fails boot on duplicate slugs or names, registers module providers, migrations and routes (`/admin/{slug}`, `{slug}.`, configured base middleware; `superadmin_only` routes require `admin-core.superadmin`).
- `admin-core.modules.disabled` turns off non-core modules of the core root.
- `modules:cache` / `modules:clear` (hooked into `optimize` / `optimize:clear`) and `modules:list`.
- `PageResolver`: `{Name}::{Page}` from `resources/js/pages` (case-exact), `App::` pages, `admin-core.pages.overrides`, and `viteEntry()` for `@vite` preloading; `resolvePage()` for the client.
- `MenuBuilder`: grouped, ordered, permission-filtered menu (`MenuGroup` / `MenuItem`).
- `admin:doctor`: npm dependencies against `admin-core.npm.json`, module cache state, environment sanity.
- Dependencies: `laravel/framework ^13.0`, `inertiajs/inertia-laravel ^3.0`, `composer/semver ^3.4`.
