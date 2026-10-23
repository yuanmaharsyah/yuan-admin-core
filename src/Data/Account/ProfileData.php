<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Account;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The `profile` prop of `Core::Account/Profile`. */
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
    ) {}
}
