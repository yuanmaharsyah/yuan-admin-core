<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Props of `Core::Auth/TwoFactorChallenge`: the authenticator code, or a recovery code. */
#[TypeScript]
final class TwoFactorChallengePageData extends Data
{
    public function __construct(
        public string $submit_href,
        public string $login_href,
    ) {}
}
