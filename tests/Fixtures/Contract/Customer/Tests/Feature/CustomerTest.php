<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Yuan\AdminCore\Testing\ModuleAssertions;
use Yuan\AdminCore\Tests\Fixtures\Contract\Customer\Models\Customer;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('redirects guests to login', function (): void {
    ModuleAssertions::assertGuestRedirected('get', '/admin/customers');
});

it('needs customers.view', function (): void {
    ModuleAssertions::assertForbiddenWithout('customers.view', 'get', '/admin/customers');
});

it('lists customers with customers.view', function (): void {
    Customer::query()->create(['name' => 'Toko Maju']);
    ModuleAssertions::actingAsLevelWith(['customers.view']);

    AssertableInertia::fromTestResponse(get('/admin/customers')->assertOk())
        ->component('Customer::Index')
        ->where('customers.items.0.name', 'Toko Maju');
});

it('audits a created customer with alamat masked', function (): void {
    $customer = Customer::query()->create(['name' => 'Toko Maju', 'alamat' => 'Jl. Merdeka 1']);

    ModuleAssertions::assertAudited($customer, 'created', masked: ['alamat']);
});
