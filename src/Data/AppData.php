<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class AppData extends Data
{
    /**
     * @param  array<string, string|int|bool|null>  $settings  The public settings by key (`kontak.whatsapp` is a wa.me link).
     */
    public function __construct(
        public string $name,
        public string $locale,
        public string $timezone,
        // Item panel state from the `admin_panel` cookie, so the first paint is correct.
        public bool $panel_open,
        // Settings → Akses Situs while it isn't open: the shell shows a banner.
        public ?SiteAccessPageData $site_access,
        // Current route name; the shell derives the active menu item from its `{slug}.` prefix.
        public ?string $route = null,
        // Settings → Umum (URLs); `logo` falls back to admin-core.branding.logo.
        public ?string $logo = null,
        public ?string $logo_landscape = null,
        public ?string $favicon = null,
        public array $settings = [],
    ) {}
}
