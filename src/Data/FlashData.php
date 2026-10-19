<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One-time messages, sent with Inertia's native flash so they are never
 * replayed from browser history: `Inertia::flash(new FlashData(success: ...)->toArray())`.
 * Unset keys are left out, so several flashes in one request merge.
 */
#[TypeScript]
final class FlashData extends Data
{
    public function __construct(
        public string|Optional $success = new Optional,
        public string|Optional $error = new Optional,
        public string|Optional $info = new Optional,
    ) {}
}
