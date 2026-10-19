<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Yuan\AdminCore\Testing\ModuleAssertions;
use Yuan\AdminCore\Tests\Fixtures\Contract\Customer\Models\Customer;
use Yuan\AdminCore\Tests\Fixtures\Contract\Invoice\Models\Invoice;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

function invoice(): Invoice
{
    $customer = Customer::query()->create(['name' => 'Toko Maju']);

    return Invoice::query()->create(['number' => 'INV-001', 'customer_id' => $customer->id, 'npwp' => '012345678901234', 'total' => 150000]);
}

it('redirects guests to login', function (): void {
    ModuleAssertions::assertGuestRedirected('get', '/admin/invoices');
    ModuleAssertions::assertGuestRedirected('post', '/admin/invoices');
});

it('needs the permission of each action', function (): void {
    $invoice = invoice();

    ModuleAssertions::assertForbiddenWithout('invoices.view', 'get', '/admin/invoices');
    ModuleAssertions::assertForbiddenWithout('invoices.create', 'post', '/admin/invoices');
    ModuleAssertions::assertForbiddenWithout('invoices.approve', 'post', "/admin/invoices/{$invoice->id}/approve");
});

it('lists invoices with invoices.view', function (): void {
    invoice();
    ModuleAssertions::actingAsLevelWith(['invoices.view']);

    AssertableInertia::fromTestResponse(get('/admin/invoices')->assertOk())
        ->component('Invoice::Index')
        ->where('invoices.items.0.customer_name', 'Toko Maju');
});

it('creates an invoice and audits it with npwp masked', function (): void {
    $customer = Customer::query()->create(['name' => 'Toko Maju']);
    ModuleAssertions::actingAsLevelWith(['invoices.view', 'invoices.create']);

    post('/admin/invoices', ['number' => 'INV-002', 'customer_id' => $customer->id, 'npwp' => '012345678901234', 'total' => 5000])
        ->assertRedirect('/admin/invoices');

    ModuleAssertions::assertAudited(Invoice::query()->where('number', 'INV-002')->sole(), 'created', masked: ['npwp']);
});

it('rejects an invoice without its required fields', function (): void {
    ModuleAssertions::actingAsLevelWith(['invoices.view', 'invoices.create']);

    post('/admin/invoices', [])->assertSessionHasErrors(['number', 'customer_id', 'total']);
});

it('approves an invoice and writes its own audit event', function (): void {
    $invoice = invoice();
    ModuleAssertions::actingAsLevelWith(['invoices.view', 'invoices.approve']);

    post("/admin/invoices/{$invoice->id}/approve")->assertRedirect('/admin/invoices');

    ModuleAssertions::assertAudited($invoice, 'approve');
});
