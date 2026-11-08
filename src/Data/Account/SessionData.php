<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Account;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One signed-in browser of the user (database sessions only). */
#[TypeScript]
final class SessionData extends Data
{
    public function __construct(
        public string $agent,
        public ?string $ip,
        public string $last_active,
        public bool $current,
    ) {}
}
