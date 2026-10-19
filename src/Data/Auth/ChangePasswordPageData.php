<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Props of `Core::Auth/ChangePassword`. */
#[TypeScript]
final class ChangePasswordPageData extends Data
{
    public function __construct(
        public string $submit_href,
        public string $back_href,
        public string $user_name,
        public ?string $level_label,
        public string $password_hint,
    ) {}
}
