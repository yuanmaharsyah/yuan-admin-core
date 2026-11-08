<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Account;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Two-step verification of the signed-in user (Akun → Keamanan), on Fortify's
 * own routes: `off`, `pending` (set up, waiting for the first code) or `on`.
 */
#[TypeScript]
final class TwoFactorData extends Data
{
    public function __construct(
        #[LiteralTypeScriptType("'off' | 'pending' | 'on'")]
        public string $state,
        // Pengaturan → Keamanan → wajib_2fa: it can't be turned off, and the panel waits for it.
        public bool $required,
        public string $enable_href,
        public string $confirm_href,
        public string $disable_href,
        public string $qr_href,
        public string $secret_href,
        public string $recovery_href,
        public string $regenerate_href,
        // Fortify asks for the password again before these changes.
        public string $confirm_status_href,
        public string $confirm_password_href,
    ) {}
}
