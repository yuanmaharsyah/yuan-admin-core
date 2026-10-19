<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The Urungkan action of a "Dipindahkan ke Sampah" toast: POST `ids` to `href`. */
#[TypeScript]
final class UndoData extends Data
{
    /**
     * @param  list<int>  $ids
     */
    public function __construct(
        public string $href,
        public array $ids,
    ) {}
}
