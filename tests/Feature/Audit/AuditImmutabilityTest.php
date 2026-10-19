<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Audit\ImmutableAuditException;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    DemoItem::query()->create(['name' => 'A']);
    Audit::record(AuditEvent::SettingsUpdated, meta: ['keys' => ['app.name']]);
});

it('throws on every way to change or remove a row, and leaves both columns as they were', function (Closure $write): void {
    $before = DB::table('activity_log')->orderBy('id')->get()->toArray();

    expect(fn () => $write(latestActivity()))->toThrow(ImmutableAuditException::class)
        ->and(DB::table('activity_log')->orderBy('id')->get()->toArray())->toEqual($before);
})->with([
    'save' => [fn (ImmutableActivity $row) => $row->forceFill(['attribute_changes' => ['x' => 1]])->save()],
    'saveQuietly' => [fn (ImmutableActivity $row) => $row->forceFill(['properties' => ['x' => 1]])->saveQuietly()],
    'delete' => [fn (ImmutableActivity $row) => $row->delete()],
    'deleteQuietly' => [fn (ImmutableActivity $row) => $row->deleteQuietly()],
    'query update' => [fn () => ImmutableActivity::query()->update(['properties' => null])],
    'query delete' => [fn () => ImmutableActivity::query()->where('event', 'created')->delete()],
    'query forceDelete' => [fn () => ImmutableActivity::query()->forceDelete()],
    'query increment' => [fn () => ImmutableActivity::query()->increment('causer_id')],
    'query decrement' => [fn () => ImmutableActivity::query()->decrement('causer_id')],
    'query upsert' => [fn () => ImmutableActivity::query()->upsert([['id' => 1, 'description' => 'x']], ['id'], ['description'])],
]);

it('refuses mass assignment', function (): void {
    expect(fn () => latestActivity()->update(['description' => 'x']))->toThrow(Exception::class)
        ->and(latestActivity()->description)->toBe('settings_updated');
});
