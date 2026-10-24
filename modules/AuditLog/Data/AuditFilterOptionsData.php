<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\AuditLog\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Data\OptionData;

/**
 * The FilterBar's current values and choices: the range (`custom` with
 * `from` / `to`), the users the viewer may see, and the modules with events.
 */
#[TypeScript]
final class AuditFilterOptionsData extends Data
{
    /**
     * @param  list<OptionData>  $users
     * @param  list<OptionData>  $modules
     */
    public function __construct(
        public string $range,
        public string $from,
        public string $to,
        public ?string $user,
        public ?string $module,
        public array $users,
        public array $modules,
        public int $max_days,
    ) {}
}
