<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Tests\Fixtures\Models\User;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;
use Yuan\AdminCore\Trash\Trash;
use Yuan\AdminCore\Trash\TrashController;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travelBack;
use function Pest\Laravel\travelTo;

uses(RefreshDatabase::class);

/**
 * @return Collection<int, DemoItem>
 */
function trashedItems(string ...$names): Collection
{
    $items = new Collection(array_values(array_map(fn (string $name): DemoItem => DemoItem::query()->create(['name' => $name]), $names)));
    $items->each->delete();

    return $items;
}

/**
 * @param  Collection<int, DemoItem>  $items
 * @return list<int>
 */
function itemIds(Collection $items): array
{
    return array_values(array_map(intval(...), $items->modelKeys()));
}

it('registers the three routes behind superadmin', function (string $name): void {
    expect(Route::getRoutes()->getByName($name)?->gatherMiddleware())->toBe(['web', 'auth', 'active', 'superadmin']);
})->with(['demo.trash', 'demo.trash.restore', 'demo.trash.destroy']);

it('refuses a model without SoftDeletes or HasDataTable', function (): void {
    expect(fn () => TrashController::routes(ImmutableActivity::class))->toThrow(InvalidArgumentException::class, 'needs SoftDeletes')
        ->and(fn () => TrashController::routes(User::class))->toThrow(InvalidArgumentException::class, 'needs DataTableModel');
});

it('is forbidden to everyone but Super Admin', function (string $method, string $uri): void {
    syncPermissions();
    $user = makeUser();
    $user->assignLevel(levelWith(['demo.view', 'demo.create', 'demo.approve']));
    $ids = itemIds(trashedItems('Sampah'));

    actingAs($user)->{$method}($uri, ['ids' => $ids])->assertForbidden();
    expect(DemoItem::onlyTrashed()->count())->toBe(1);
})->with([['get', '/admin/demo/trash'], ['post', '/admin/demo/trash/restore'], ['delete', '/admin/demo/trash']]);

it('lists only trashed rows, newest deletion first, with search', function (): void {
    DemoItem::query()->create(['name' => 'Masih ada']);
    travelTo(now()->subMinute());
    trashedItems('Lama');
    travelBack();
    trashedItems('Baru', 'Bukan');

    AssertableInertia::fromTestResponse(actingAs(superAdminUser())->get('/admin/demo/trash?search=ba')->assertOk())
        ->component('Core::Trash/Index')
        ->where('items.items.0.label', 'Baru')
        ->count('items.items', 1)
        ->where('table.search', 'ba')
        ->where('table.searchable', true)
        ->where('trash.module_label', 'Demo')
        ->where('trash.restore_href', route('demo.trash.restore'))
        ->where('breadcrumbs.items.1.label', 'Sampah');

    AssertableInertia::fromTestResponse(get('/admin/demo/trash'))->count('items.items', 3)->where('items.items.2.label', 'Lama');
});

it('restores the selected rows in one audit batch', function (): void {
    $items = trashedItems('Satu', 'Dua');
    $alive = DemoItem::query()->create(['name' => 'Hidup']);

    actingAs(superAdminUser())->from('/admin/demo/trash')
        ->post('/admin/demo/trash/restore', ['ids' => [...itemIds($items), $alive->id]])
        ->assertRedirect('/admin/demo/trash');

    $rows = ImmutableActivity::query()->where('event', 'restored')->get();

    expect(DemoItem::onlyTrashed()->count())->toBe(0)
        ->and(session(SessionKey::FLASH_DATA))->toBe(['success' => '2 data dipulihkan'])
        ->and($rows)->toHaveCount(2)
        ->and($rows->pluck('batch_uuid')->unique())->toHaveCount(1)
        ->and($rows->first()?->batch_uuid)->toBeString();
});

it('deletes the selected trashed rows for good, never a live one', function (): void {
    $items = trashedItems('Satu');
    $alive = DemoItem::query()->create(['name' => 'Hidup']);

    actingAs(superAdminUser())
        ->delete('/admin/demo/trash', ['ids' => [...itemIds($items), $alive->id]])
        ->assertRedirect(route('demo.trash'));

    expect(session(SessionKey::FLASH_DATA))->toBe(['success' => 'Data dihapus permanen'])
        ->and(DemoItem::withTrashed()->pluck('name')->all())->toBe(['Hidup'])
        ->and(ImmutableActivity::query()->where('event', 'force_deleted')->count())->toBe(1);
});

it('validates the selection', function (mixed $ids): void {
    actingAs(superAdminUser())->post('/admin/demo/trash/restore', ['ids' => $ids])->assertSessionHasErrors('ids');
})->with([[[]], ['x'], [null]]);

it('soft-deletes in one batch and offers Urungkan only to Super Admin', function (): void {
    $items = new Collection([DemoItem::query()->create(['name' => 'Satu']), DemoItem::query()->create(['name' => 'Dua'])]);

    actingAs(superAdminUser());
    Trash::delete($items, 'demo');

    $flash = session(SessionKey::FLASH_DATA);
    $rows = ImmutableActivity::query()->where('event', 'deleted')->get();

    expect(DemoItem::onlyTrashed()->count())->toBe(2)
        ->and($rows->pluck('batch_uuid')->unique())->toHaveCount(1)
        ->and($flash)->toMatchArray([
            'success' => 'Dipindahkan ke Sampah',
            'description' => '2 item',
            'undo' => ['href' => route('demo.trash.restore'), 'ids' => itemIds($items)],
        ]);
});

it('names a single deleted row and gives no undo to other users', function (): void {
    actingAs(makeUser());
    Trash::delete(new Collection([DemoItem::query()->create(['name' => 'Satu'])]), 'demo');

    expect(session(SessionKey::FLASH_DATA))->toBe(['success' => 'Dipindahkan ke Sampah', 'description' => 'Demo Satu']);
});
