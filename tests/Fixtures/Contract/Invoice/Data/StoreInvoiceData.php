<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Contract\Invoice\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class StoreInvoiceData extends Data
{
    public function __construct(
        #[Max(30)]
        public string $number,
        #[Exists('contract_customers', 'id')]
        public int $customer_id,
        #[Max(20)]
        public ?string $npwp,
        #[Min(0)]
        public int $total,
    ) {}
}
