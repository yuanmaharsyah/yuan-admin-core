<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Props of `Core::Auth/ConfirmPassword`: asked before a sensitive change (two-factor setup). */
#[TypeScript]
final class ConfirmPasswordPageData extends Data
{
    public function __construct(
        public string $submit_href,
    ) {}
}
