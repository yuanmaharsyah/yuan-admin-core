<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Yuan\AdminCore\Data\MenuItemData;
use Yuan\AdminCore\Data\PaginatedData;

/**
 * Page $page of 3 items per page over 7 rows, built for the given request URI.
 *
 * @return PaginatedData<MenuItemData>
 */
function paginated(string $uri, int $page): PaginatedData
{
    app()->instance('request', Request::create($uri));
    $rows = array_map(fn (int $i): array => ['slug' => "s{$i}", 'label' => "L{$i}", 'icon' => 'box', 'href' => "/x/{$i}"], range(1, 7));

    $paginator = new LengthAwarePaginator(array_slice($rows, ($page - 1) * 3, 3), 7, 3, $page, ['path' => LengthAwarePaginator::resolveCurrentPath()]);

    return PaginatedData::fromPaginator($paginator, MenuItemData::class);
}

it('maps items through the Data class and fills meta', function (): void {
    $data = paginated('/admin/demo', 1);

    expect($data->items)->toHaveCount(3)->toContainOnlyInstancesOf(MenuItemData::class)
        ->and($data->items[0]->slug)->toBe('s1')
        ->and($data->meta->toArray())->toBe(['current_page' => 1, 'per_page' => 3, 'total' => 7, 'last_page' => 3]);
});

it('gives null prev on the first page and null next on the last', function (): void {
    expect(paginated('/admin/demo', 1)->links->prev)->toBeNull()
        ->and(paginated('/admin/demo', 3)->links->next)->toBeNull();
});

it('keeps the filter in every link on page 2 of a filtered list', function (): void {
    $links = paginated('/admin/demo?filter%5Bstatus%5D=paid&page=2', 2)->links;

    foreach ([$links->first, $links->last, $links->prev, $links->next] as $link) {
        expect(urldecode((string) $link))->toContain('filter[status]=paid');
    }

    expect(urldecode((string) $links->next))->toContain('page=3');
});
