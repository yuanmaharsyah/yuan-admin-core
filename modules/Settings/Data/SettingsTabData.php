<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Settings\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One Settings tab (a registry group); `lock_version` is its version for the stale-update check. */
#[TypeScript]
final class SettingsTabData extends Data
{
    /**
     * @param  list<SettingFieldData>  $fields
     */
    public function __construct(
        public string $key,
        public string $label,
        public int $lock_version,
        public array $fields,
    ) {}
}
