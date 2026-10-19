<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Contract\Invoice\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class InvoiceData extends Data
{
    public function __construct(
        public int $id,
        public string $number,
        public int $customer_id,
        public ?string $npwp,
        public int $total,
        public string $updated_at,
    ) {}
}
