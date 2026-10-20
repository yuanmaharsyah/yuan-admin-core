<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\DataTable\VisibilityScope;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;
use Yuan\AdminCore\Validation\VisibleRecord;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function passesVisibleRecord(mixed $value): bool
{
    return Validator::make(['item_id' => $value], ['item_id' => [new VisibleRecord(DemoItem::class)]])->passes();
}

it('accepts a live record and refuses missing, trashed and malformed ids', function (): void {
    actingAs(makeUser());
    $live = DemoItem::query()->create(['name' => 'Ada']);
    $trashed = tap(DemoItem::query()->create(['name' => 'Dibuang']))->delete();

    expect(passesVisibleRecord($live->id))->toBeTrue()
        ->and(passesVisibleRecord((string) $live->id))->toBeTrue()
        ->and(passesVisibleRecord($trashed->id))->toBeFalse()
        ->and(passesVisibleRecord(999))->toBeFalse()
        ->and(passesVisibleRecord('1 or 1=1'))->toBeFalse()
        ->and(passesVisibleRecord([1]))->toBeFalse();
});

it('checks through visibleTo() with the signed-in user', function (): void {
    $user = makeUser();
    actingAs($user);
    $item = DemoItem::query()->create(['name' => 'Ada']);

    $calls = VisibilityScope::record(fn () => passesVisibleRecord($item->id));

    expect($calls)->toContain(['model' => DemoItem::class, 'user' => $user->id]);
});

it('refuses a model without the visibility hook', function (): void {
    expect(fn () => new VisibleRecord(ImmutableActivity::class))->toThrow(InvalidArgumentException::class);
});
