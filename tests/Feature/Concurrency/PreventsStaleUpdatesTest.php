<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Concurrency\StaleUpdateException;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;

use function Pest\Laravel\travel;

uses(RefreshDatabase::class);

it('saves when the row is unchanged since the form loaded', function (): void {
    $item = DemoItem::query()->create(['name' => 'Lama']);
    $loadedAt = $item->updated_at?->toIso8601String() ?? '';
    travel(5)->seconds();

    $item->updateUnlessStale(['name' => 'Baru'], $loadedAt);

    expect($item->fresh()?->name)->toBe('Baru');
});

it('refuses and writes nothing when someone saved in between', function (): void {
    $item = DemoItem::query()->create(['name' => 'Lama']);
    $loadedAt = $item->updated_at?->toIso8601String() ?? '';
    travel(5)->seconds();
    DemoItem::query()->findOrFail($item->id)->update(['name' => 'Orang lain']);
    $audited = ImmutableActivity::query()->count();

    expect(fn () => $item->updateUnlessStale(['name' => 'Saya'], $loadedAt))
        ->toThrow(StaleUpdateException::class, 'Data ini telah diubah oleh pengguna lain.');

    expect($item->fresh()?->name)->toBe('Orang lain')
        ->and(ImmutableActivity::query()->count())->toBe($audited);
});

it('compares the instant, whatever the offset the client sends', function (): void {
    $item = DemoItem::query()->create(['name' => 'Lama']);
    $loadedAt = $item->updated_at?->setTimezone('Asia/Jakarta')->toIso8601String() ?? '';

    $item->updateUnlessStale(['name' => 'Baru'], $loadedAt);

    expect($item->fresh()?->name)->toBe('Baru');
});

it('answers HTTP 409', function (): void {
    expect((new StaleUpdateException)->getStatusCode())->toBe(409);
});
