<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Account;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The Profil tab of `Core::Account/Settings`. */
#[TypeScript]
final class ProfileData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $username,
        public ?string $level_label,
        public int $lock_version,
        public string $submit_href,
        public string $password_href,
        // The photo (a signed, short-lived URL) and where to send or remove it.
        public ?string $avatar_url = null,
        public string $avatar_submit_href = '',
        public ?string $avatar_destroy_href = null,
    ) {}
}
