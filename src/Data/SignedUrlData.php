<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A short-lived signed URL (attachment downloads). */
#[TypeScript]
final class SignedUrlData extends Data
{
    public function __construct(
        public string $url,
    ) {}
}
