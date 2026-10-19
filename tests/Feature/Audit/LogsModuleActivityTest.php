<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;

uses(RefreshDatabase::class);

it('logs a create with the module slug as log name and the label', function (): void {
    DemoItem::query()->create(['name' => 'Surat 1', 'pin' => '9999']);

    $row = latestActivity();
    expect($row->only(['event', 'log_name']))->toBe(['event' => 'created', 'log_name' => 'demo'])
        ->and($row->attribute_changes?->get('attributes'))->toMatchArray(['name' => 'Surat 1'])
        ->and($row->attribute_changes?->get('attributes'))->not->toHaveKey('pin')
        ->and($row->getProperty('meta.subject_label'))->toBe('Surat 1');
});

it('logs only the dirty fillable attributes of an update', function (): void {
    $item = DemoItem::query()->create(['name' => 'A', 'no_hp' => null]);
    $item->update(['name' => 'B']);

    expect(latestActivity()->attribute_changes?->all())->toBe([
        'attributes' => ['name' => 'B'],
        'old' => ['name' => 'A'],
    ]);
});

it('skips an update that changes no logged attribute', function (): void {
    $item = DemoItem::query()->create(['name' => 'A']);
    $item->update(['pin' => '1234']);
    $item->touch();

    expect(ImmutableActivity::query()->count())->toBe(1);
});

it('logs deleted, restored and force_deleted separately', function (): void {
    $item = DemoItem::query()->create(['name' => 'A']);
    $item->delete();
    $item->restore();
    $item->forceDelete();

    expect(ImmutableActivity::query()->orderBy('id')->pluck('event')->all())
        ->toBe(['created', 'deleted', 'restored', 'force_deleted'])
        ->and(latestActivity()->getProperty('meta.subject_label'))->toBe('A');
});

it('falls back to #id for the label', function (): void {
    $item = DemoItem::query()->create(['nik' => '3201234567890001']);

    expect($item->auditLabel())->toBe('#'.$item->id);
});
