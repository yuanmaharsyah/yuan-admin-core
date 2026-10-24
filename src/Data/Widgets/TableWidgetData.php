<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Widgets;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Data\OptionData;

/** A `table` widget (`WidgetTable`): `columns` as value = row key, label = heading; at most five rows of text. */
#[TypeScript]
final class TableWidgetData extends Data
{
    public const int MAX_ROWS = 5;

    /**
     * @param  list<OptionData>  $columns
     * @param  list<array<string, string|null>>  $rows
     */
    public function __construct(
        public array $columns,
        public array $rows,
    ) {
        $this->rows = array_slice($rows, 0, self::MAX_ROWS);
    }
}
