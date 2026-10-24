<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Settings\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** `Settings::Index`: every tab of the registry and the open one (`?tab=`). */
#[TypeScript]
final class SettingsPageData extends Data
{
    /**
     * @param  list<SettingsTabData>  $tabs
     */
    public function __construct(
        public array $tabs,
        public string $tab,
    ) {}
}
