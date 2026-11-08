<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Widgets;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A `list` widget (`ListCard`): at most six rows, e.g. the latest records. */
#[TypeScript]
final class ListWidgetData extends Data
{
    public const int MAX_ROWS = 6;

    /**
     * @param  list<ListRowData>  $rows
     */
    public function __construct(
        public array $rows,
    ) {
        $this->rows = array_slice($rows, 0, self::MAX_ROWS);
    }
}
