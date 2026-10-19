<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Props of `Core::Auth/ResetPassword`. */
#[TypeScript]
final class ResetPasswordPageData extends Data
{
    public function __construct(
        public string $submit_href,
        public string $login_href,
        public string $forgot_href,
        public string $email,
        public string $token,
        // False for a used, expired or unknown link: the page asks for a new one.
        public bool $token_valid,
        public string $password_hint,
    ) {}
}
