<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Permissions\Level;

#[TypeScript]
final class LevelData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $label,
    ) {}

    public static function fromLevel(Level $level): self
    {
        return new self(id: $level->id, name: $level->name, label: $level->label);
    }
}
