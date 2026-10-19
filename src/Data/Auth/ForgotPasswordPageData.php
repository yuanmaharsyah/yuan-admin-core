<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Props of `Core::Auth/ForgotPassword`. */
#[TypeScript]
final class ForgotPasswordPageData extends Data
{
    public function __construct(
        public string $submit_href,
        public string $login_href,
        // Set once a link was requested; the page then shows the "sent" state.
        public ?string $status,
        // Seconds before another link may be requested (auth.passwords throttle).
        public int $resend_after,
    ) {}
}
