<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Props of `Core::Auth/Login`. */
#[TypeScript]
final class LoginPageData extends Data
{
    public function __construct(
        public string $submit_href,
        // Null when password reset is off.
        public ?string $forgot_href,
        // Fortify's session status, e.g. after a password reset.
        public ?string $status,
    ) {}
}
