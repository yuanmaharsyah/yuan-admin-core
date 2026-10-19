<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Contract\Invoice\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Tests\Fixtures\Contract\Invoice\Models\Invoice;

#[TypeScript]
final class InvoiceListItemData extends Data
{
    public function __construct(
        public int $id,
        public string $number,
        public string $customer_name,
        public int $total,
    ) {}

    public static function fromModel(Invoice $invoice): self
    {
        return new self(id: $invoice->id, number: $invoice->number, customer_name: $invoice->customer->name, total: $invoice->total);
    }
}
