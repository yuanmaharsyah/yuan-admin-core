<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Props of `Core::Errors/NoAccess`: signed in, but no module is permitted. */
#[TypeScript]
final class NoAccessPageData extends Data
{
    public function __construct(
        public string $logout_href,
    ) {}
}
