<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Data\PermissionMatrixRowData;
use Yuan\AdminCore\Events\LevelPermissionsUpdated;
use Yuan\AdminCore\Permissions\GuardRailViolation;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Permissions\PermissionMatrix;

uses(RefreshDatabase::class);

beforeEach(fn () => syncPermissions());

/**
 * @return array<int, mixed>
 */
function permissionsOf(Level $level): array
{
    return $level->permissions()->orderBy('name')->pluck('name')->all();
}

it('builds a row per enabled, non-superadmin_only module with its declared actions, in menu-group order', function (): void {
    $matrix = app(PermissionMatrix::class)->build(levelWith(['demo.view', 'demo.approve']));

    expect($matrix->level->name)->toBe('staf')
        ->and($matrix->columns)->toBe(['view', 'create', 'edit', 'delete', 'download', 'approve'])
        ->and(array_map(fn (PermissionMatrixRowData $row): string => "{$row->group}/{$row->slug}", $matrix->rows))->toBe(['Master Data/demo', 'Master Data/hidden', 'Sistem/notes'])
        ->and($matrix->rows[0]->actions)->toBe(['view', 'create', 'approve'])
        ->and($matrix->rows[0]->granted)->toEqualCanonicalizing(['view', 'approve'])
        ->and($matrix->rows[2]->granted)->toBe([]);
});

it('adds view when another action is granted', function (): void {
    $level = levelWith([]);

    app(PermissionMatrix::class)->save($level, ['demo' => ['create']]);

    expect(permissionsOf($level))->toBe(['demo.create', 'demo.view']);
});

it('clears the row when view is removed', function (): void {
    $level = levelWith(['demo.view', 'demo.create', 'notes.view']);

    app(PermissionMatrix::class)->save($level, ['demo' => ['create'], 'notes' => ['view']]);

    expect(permissionsOf($level))->toBe(['notes.view']);
});

it('refuses the protected level', function (): void {
    expect(fn () => app(PermissionMatrix::class)->save(Level::superAdmin(), ['demo' => ['view']]))
        ->toThrow(GuardRailViolation::class);
});

it('refuses modules outside the matrix and undeclared actions', function (array $grants): void {
    expect(fn () => app(PermissionMatrix::class)->save(levelWith([]), $grants))->toThrow(InvalidArgumentException::class);
})->with([
    'superadmin_only module' => [['admin-only' => ['view']]],
    'unknown module' => [['invoices' => ['view']]],
    'undeclared action' => [['demo' => ['view', 'edit']]],
]);

it('writes one batched permissions_updated with the diff and fires the event', function (): void {
    Event::fake([LevelPermissionsUpdated::class]);
    $level = levelWith(['notes.view']);

    app(PermissionMatrix::class)->save($level, ['demo' => ['view', 'approve']]);

    $row = ImmutableActivity::query()->where('event', 'permissions_updated')->sole();
    expect($row->only(['event', 'log_name', 'subject_id']))->toBe(['event' => 'permissions_updated', 'log_name' => 'levels', 'subject_id' => $level->id])
        ->and($row->batch_uuid)->toBeString()
        ->and($row->getProperty('meta'))->toBe(['added' => ['demo.view', 'demo.approve'], 'removed' => ['notes.view'], 'subject_label' => 'Staf']);
    Event::assertDispatched(LevelPermissionsUpdated::class, fn (LevelPermissionsUpdated $event): bool => $event->level->is($level) && $event->removed === ['notes.view']);
});

it('writes nothing when nothing changes', function (): void {
    $level = levelWith(['demo.view']);
    $before = ImmutableActivity::query()->count();

    app(PermissionMatrix::class)->save($level, ['demo' => ['view']]);

    expect(ImmutableActivity::query()->count())->toBe($before);
});

it('rolls back the permissions when the audit write fails', function (): void {
    $level = levelWith(['notes.view']);
    ImmutableActivity::creating(fn () => throw new RuntimeException('audit down'));

    expect(fn () => app(PermissionMatrix::class)->save($level, ['demo' => ['view']]))->toThrow(RuntimeException::class, 'audit down')
        ->and(permissionsOf($level->fresh() ?? $level))->toBe(['notes.view']);
});

it('applies a save on the next permission check', function (): void {
    $level = levelWith([]);
    $user = makeUser();
    $user->assignLevel($level);
    expect($user->can('demo.view'))->toBeFalse();

    app(PermissionMatrix::class)->save($level, ['demo' => ['view']]);

    expect($user->fresh()?->can('demo.view'))->toBeTrue();
});
