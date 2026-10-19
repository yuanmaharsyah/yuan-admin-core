<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class AuthData extends Data
{
    public function __construct(
        public ?UserData $user,
        public bool $is_super_admin,
    ) {}
}
