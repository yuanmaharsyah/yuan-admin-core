<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Illuminate\Contracts\Auth\Authenticatable;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
    ) {}

    public static function fromUser(Authenticatable $user): self
    {
        return new self(
            id: (int) $user->getAuthIdentifier(),
            name: (string) data_get($user, 'name'),
            email: (string) data_get($user, 'email'),
        );
    }
}
