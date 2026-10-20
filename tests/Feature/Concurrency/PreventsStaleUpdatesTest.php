<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Concurrency\StaleUpdateException;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;

use function Pest\Laravel\freezeSecond;

uses(RefreshDatabase::class);

it('raises lock_version by one on every update, whatever the path', function (): void {
    $item = DemoItem::query()->create(['name' => 'Lama']);
    expect($item->fresh()?->lock_version)->toBe(0);

    $item->update(['name' => 'Dua']);
    DemoItem::query()->findOrFail($item->id)->update(['name' => 'Tiga']);
    $item->refresh()->updateUnlessStale(['name' => 'Empat'], 2);

    expect($item->fresh()?->lock_version)->toBe(3)
        ->and($item->fresh()?->name)->toBe('Empat');
});

it('saves when the version is the one the form loaded', function (): void {
    $item = DemoItem::query()->create(['name' => 'Lama']);

    $item->updateUnlessStale(['name' => 'Baru'], 0);

    expect($item->fresh()?->only(['name', 'lock_version']))->toBe(['name' => 'Baru', 'lock_version' => 1]);
});

it('refuses the second of two saves in the same second', function (): void {
    $item = DemoItem::query()->create(['name' => 'Lama']);
    $first = DemoItem::query()->findOrFail($item->id);
    $second = DemoItem::query()->findOrFail($item->id);
    freezeSecond();

    $first->updateUnlessStale(['name' => 'Orang lain'], 0);
    $audited = ImmutableActivity::query()->count();

    expect(fn () => $second->updateUnlessStale(['name' => 'Saya'], 0))
        ->toThrow(StaleUpdateException::class, 'Data ini telah diubah oleh pengguna lain.');

    expect($item->fresh()?->only(['name', 'lock_version']))->toBe(['name' => 'Orang lain', 'lock_version' => 1])
        ->and(ImmutableActivity::query()->count())->toBe($audited);
});

it('answers HTTP 409', function (): void {
    expect((new StaleUpdateException)->getStatusCode())->toBe(409);
});
