<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
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
        /**
         * Social sign-in buttons (`admin-core.auth.social`): room reserved for
         * them, not wired yet (restyle 03 plan, owner exception); empty hides them.
         *
         * @var list<string>
         */
        #[LiteralTypeScriptType("('google' | 'github')[]")]
        public array $social = [],
        // Null while registration is off (Pengaturan → Keamanan).
        public ?string $register_href = null,
    ) {}
}
