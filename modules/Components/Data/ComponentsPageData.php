<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Components\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Data\DataTableStateData;
use Yuan\AdminCore\Data\PaginatedData;

/** Props of `Components::Index`: a small fixed page for the live DataTable example. */
#[TypeScript]
final class ComponentsPageData extends Data
{
    /**
     * @param  PaginatedData<SampleRowData>  $table
     */
    public function __construct(
        public PaginatedData $table,
        public DataTableStateData $state,
    ) {}
}
