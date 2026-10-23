<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One module of the matrix, in its menu group: the actions it declares and
 * those the level has.
 */
#[TypeScript]
final class PermissionMatrixRowData extends Data
{
    /**
     * @param  list<string>  $actions
     * @param  list<string>  $granted
     */
    public function __construct(
        public string $slug,
        public string $label,
        public string $group,
        public array $actions,
        public array $granted,
    ) {}
}
