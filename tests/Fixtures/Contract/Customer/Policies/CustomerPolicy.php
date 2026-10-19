<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Contract\Customer\Policies;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Yuan\AdminCore\Tests\Fixtures\Contract\Customer\Models\Customer;

final class CustomerPolicy
{
    public function viewAny(Authorizable $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(Authorizable $user, Customer $customer): bool
    {
        return $user->can('customers.view');
    }
}
