<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $query
 * @return list<string|null>
 */
function listedNames(array $query): array
{
    $page = DemoItem::query()->dataTable(Request::create('/admin/demo', 'GET', $query));

    return array_values(array_map(fn (DemoItem $item): ?string => $item->name, $page->items()));
}

beforeEach(function (): void {
    foreach ([['Budi', 'paid'], ['Andi', 'draft'], ['Citra 100%', 'draft'], ['Dewi_x', 'paid']] as [$name, $status]) {
        (new DemoItem)->forceFill(['name' => $name, 'status' => $status])->save(); // status is a fixture-only column, not fillable
    }
});

it('lists newest first by default', function (): void {
    expect(listedNames([]))->toBe(['Dewi_x', 'Citra 100%', 'Andi', 'Budi']);
});

it('searches the declared columns, ignoring case and blank terms', function (): void {
    expect(listedNames(['search' => 'dI']))->toBe(['Andi', 'Budi'])
        ->and(listedNames(['search' => '100']))->toBe(['Citra 100%'])
        ->and(listedNames(['search' => '   ']))->toHaveCount(4);
});

it('filters exactly on a declared filter and combines it with search', function (): void {
    expect(listedNames(['filter' => ['status' => 'paid']]))->toBe(['Dewi_x', 'Budi'])
        ->and(listedNames(['filter' => ['status' => 'paid'], 'search' => 'bu']))->toBe(['Budi']);
});

it('sorts ascending and descending by a sortable column', function (): void {
    expect(listedNames(['sort' => 'name']))->toBe(['Andi', 'Budi', 'Citra 100%', 'Dewi_x'])
        ->and(listedNames(['sort' => '-name']))->toBe(['Dewi_x', 'Citra 100%', 'Budi', 'Andi']);
});

it('rejects an undeclared sort or filter', function (array $query, string $exception): void {
    expect(fn () => listedNames($query))->toThrow($exception);
})->with([
    'sort' => [['sort' => 'nik'], InvalidSortQuery::class],
    'filter' => [['filter' => ['nik' => '1']], InvalidFilterQuery::class],
]);

it('pages with an allowed size and falls back to 25 otherwise', function (int|string $perPage, int $expected): void {
    $page = DemoItem::query()->dataTable(Request::create('/', 'GET', ['per_page' => $perPage]));

    expect($page->perPage())->toBe($expected);
})->with([
    [10, 10], [100, 100], [500, 25], [7, 25], ['abc', 25],
]);

it('describes the list state for the frontend', function (): void {
    $state = DemoItem::dataTableState(Request::create('/', 'GET', [
        'search' => ' bu ', 'sort' => '-name', 'per_page' => 50,
        'filter' => ['status' => 'paid', 'nik' => '1', 'name' => ''],
    ]));

    expect($state->toArray())->toMatchArray([
        'search' => 'bu',
        'sort' => '-name',
        'filter' => ['status' => 'paid'],
        'per_page' => 50,
        'per_page_options' => [10, 25, 50, 100],
        'sortable' => ['name', 'created_at'],
        'searchable' => true,
    ])->and($state->filters[0]->key)->toBe('status');
});

it('drops a sort the model does not allow from the state', function (): void {
    expect(DemoItem::dataTableState(Request::create('/', 'GET', ['sort' => 'nik']))->sort)->toBeNull();
});
