<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Audit;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A record's Riwayat tab (frontend-behavior §8.3a): one page of 20 of its
 * events, newest first; `next_page` asks for more (`history_page`).
 */
#[TypeScript]
final class ActivityHistoryData extends Data
{
    /**
     * @param  list<ActivityItemData>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public ?int $next_page,
    ) {}
}
