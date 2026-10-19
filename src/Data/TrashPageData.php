<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The `trash` prop of `Core::Trash/Index`: the module and the URLs the page posts to. */
#[TypeScript]
final class TrashPageData extends Data
{
    public function __construct(
        public string $module_label,
        public string $index_href,
        public string $restore_href,
        public string $destroy_href,
    ) {}
}
