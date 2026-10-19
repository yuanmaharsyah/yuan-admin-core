<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Contract\Customer\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Yuan\AdminCore\Data\PaginatedData;
use Yuan\AdminCore\Tests\Fixtures\Contract\Customer\Data\CustomerListItemData;
use Yuan\AdminCore\Tests\Fixtures\Contract\Customer\Models\Customer;

final class CustomerController
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Customer::class);

        return Inertia::render('Customer::Index', [
            'customers' => PaginatedData::fromPaginator(Customer::query()->orderBy('name')->paginate(), CustomerListItemData::class),
        ]);
    }
}
