<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Props of `Core::Auth/Register` (only while Pengaturan → Keamanan → registrasi is on). */
#[TypeScript]
final class RegisterPageData extends Data
{
    /**
     * @param  list<string>  $social
     */
    public function __construct(
        public string $submit_href,
        public string $login_href,
        public string $password_hint,
        #[LiteralTypeScriptType("('google' | 'github')[]")]
        public array $social = [],
    ) {}
}
