<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The `downloads_list` prop of `Core::Downloads/Index`: the user's own exports, newest first. */
#[TypeScript]
final class DownloadListData extends Data
{
    /**
     * @param  list<DownloadItemData>  $items
     */
    public function __construct(
        public array $items,
        public int $ttl_hours,
    ) {}
}
