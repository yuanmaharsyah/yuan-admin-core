<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Contract\Invoice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Data\PaginatedData;
use Yuan\AdminCore\Tests\Fixtures\Contract\Invoice\Data\InvoiceListItemData;
use Yuan\AdminCore\Tests\Fixtures\Contract\Invoice\Data\StoreInvoiceData;
use Yuan\AdminCore\Tests\Fixtures\Contract\Invoice\Models\Invoice;

final class InvoiceController
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Invoice::class);

        return Inertia::render('Invoice::Index', [
            'invoices' => PaginatedData::fromPaginator(Invoice::query()->with('customer')->latest('id')->paginate(), InvoiceListItemData::class),
        ]);
    }

    public function store(StoreInvoiceData $data): RedirectResponse
    {
        Gate::authorize('create', Invoice::class);

        Invoice::query()->create($data->toArray());

        return to_route('invoices.index');
    }

    public function approve(Invoice $invoice): RedirectResponse
    {
        Gate::authorize('approve', $invoice);

        Audit::record('approve', $invoice);

        return to_route('invoices.index');
    }
}
