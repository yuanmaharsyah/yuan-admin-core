<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Props of `Core::Errors/SiteAccess`: a public page while the site is in maintenance or coming soon. */
#[TypeScript]
final class SiteAccessPageData extends Data
{
    public function __construct(
        #[LiteralTypeScriptType("'maintenance' | 'coming_soon'")]
        public string $mode,
        public ?string $message,
    ) {}
}
