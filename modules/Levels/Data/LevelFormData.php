<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Levels\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Permissions\Level;

/** A level on its edit page. */
#[TypeScript]
final class LevelFormData extends Data
{
    public function __construct(
        public int $id,
        public string $label,
        public ?string $description,
        public int $lock_version,
    ) {}

    public static function fromLevel(Level $level): self
    {
        return new self(id: $level->id, label: $level->label, description: $level->description, lock_version: $level->lock_version);
    }
}
