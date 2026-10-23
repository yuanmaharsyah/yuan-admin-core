<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Exports;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The state of a queued download (frontend-behavior §8.4). */
#[TypeScript]
enum ExportStatus: string
{
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
    /** Shown only: a ready export past `expires_at`, waiting for `model:prune`. Never stored. */
    case Expired = 'expired';
}
