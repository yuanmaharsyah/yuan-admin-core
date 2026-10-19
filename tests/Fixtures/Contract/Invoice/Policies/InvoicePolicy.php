<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Contract\Invoice\Policies;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Yuan\AdminCore\Tests\Fixtures\Contract\Invoice\Models\Invoice;

final class InvoicePolicy
{
    public function viewAny(Authorizable $user): bool
    {
        return $user->can('invoices.view');
    }

    public function view(Authorizable $user, Invoice $invoice): bool
    {
        return $user->can('invoices.view');
    }

    public function create(Authorizable $user): bool
    {
        return $user->can('invoices.create');
    }

    public function update(Authorizable $user, Invoice $invoice): bool
    {
        return $user->can('invoices.edit');
    }

    public function delete(Authorizable $user, Invoice $invoice): bool
    {
        return $user->can('invoices.delete');
    }

    public function download(Authorizable $user): bool
    {
        return $user->can('invoices.download');
    }

    public function approve(Authorizable $user, Invoice $invoice): bool
    {
        return $user->can('invoices.approve');
    }
}
