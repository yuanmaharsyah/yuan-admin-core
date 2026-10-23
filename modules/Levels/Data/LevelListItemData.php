<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Levels\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class LevelListItemData extends Data
{
    public function __construct(
        public int $id,
        public string $label,
        public ?string $description,
        public int $users_count,
        public bool $is_protected,
    ) {}
}
