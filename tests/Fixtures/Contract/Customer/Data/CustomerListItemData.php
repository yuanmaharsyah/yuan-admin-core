<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Contract\Customer\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class CustomerListItemData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}
