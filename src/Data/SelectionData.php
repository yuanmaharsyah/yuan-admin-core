<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Input of a bulk action on the selected rows (bulk delete, restore, permanent delete). */
#[TypeScript]
final class SelectionData extends Data
{
    /**
     * @param  list<int>  $ids
     */
    public function __construct(
        public array $ids,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'distinct'],
        ];
    }
}
