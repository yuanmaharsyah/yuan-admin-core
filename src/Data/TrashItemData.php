<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Trash\Trash;

/** One row of a module's Trash. `label` is the model's `auditLabel()`. */
#[TypeScript]
final class TrashItemData extends Data
{
    public function __construct(
        public int $id,
        public string $label,
        public string $deleted_at,
    ) {}

    public static function fromModel(Model $model): self
    {
        $deletedAt = $model->getAttribute('deleted_at');

        return new self(
            id: (int) $model->getKey(),
            label: Trash::label($model),
            deleted_at: $deletedAt instanceof DateTimeInterface ? $deletedAt->format(DATE_ATOM) : '',
        );
    }
}
