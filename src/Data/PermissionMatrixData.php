<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The Hak Akses matrix of one level (spec §6.1). `columns` = the standard
 * actions, then every custom action in module order.
 */
#[TypeScript]
final class PermissionMatrixData extends Data
{
    /**
     * @param  list<string>  $columns
     * @param  list<PermissionMatrixRowData>  $rows
     */
    public function __construct(
        public LevelData $level,
        public array $columns,
        public array $rows,
    ) {}
}
