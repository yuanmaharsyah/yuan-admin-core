<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class AppData extends Data
{
    public function __construct(
        public string $name,
        public string $locale,
        public string $timezone,
        // Item panel state from the `admin_panel` cookie, so the first paint is correct.
        public bool $panel_open,
        public ?string $maintenance_banner,
        // Current route name; the shell derives the active menu item from its `{slug}.` prefix.
        public ?string $route = null,
    ) {}
}
