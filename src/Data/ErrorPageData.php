<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Props of `Core::Errors/Show` (403, 404, 419, 500, 503). */
#[TypeScript]
final class ErrorPageData extends Data
{
    public function __construct(
        public int $status,
        // Where "back" goes: the home page when signed in, else the login page.
        public string $home_href,
        public bool $signed_in,
    ) {}
}
