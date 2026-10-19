<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Contract\Invoice\Exports;

/**
 * Column headings only; the export contract (`ExportManager`) arrives in Phase 06.
 */
final class InvoiceExport
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Nomor', 'Pelanggan', 'Total'];
    }
}
