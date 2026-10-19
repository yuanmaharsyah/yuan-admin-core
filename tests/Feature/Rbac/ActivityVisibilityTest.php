<?php

declare(strict_types=1);

use Illuminate\Auth\Events\Failed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Tests\Fixtures\Models\User;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Whether a row was caused by or is about this user.
 */
function touches(ImmutableActivity $row, User $user): bool
{
    return ($row->causer_type === $user->getMorphClass() && $row->causer_id === $user->id)
        || ($row->subject_type === $user->getMorphClass() && $row->subject_id === $user->id);
}

it('hides every row caused by or about a Super Admin from other viewers', function (): void {
    syncPermissions();
    $superAdmin = superAdminUser();
    $viewer = makeUser('Viewer');
    $viewer->assignLevel(levelWith(['demo.view']));
    $item = DemoItem::query()->create(['name' => 'Surat 1']);

    actingAs($superAdmin);
    $item->update(['name' => 'Surat 1a']);
    Audit::record(AuditEvent::SettingsUpdated);
    auth()->forgetUser();
    event(new Failed('web', null, ['email' => $superAdmin->email, 'password' => 'x']));
    Audit::record(AuditEvent::LoginLocked, meta: ['username' => 'nobody']);
    actingAs($viewer);
    $item->update(['name' => 'Surat 1b']);

    $all = ImmutableActivity::query()->get();
    $hidden = $all->filter(fn (ImmutableActivity $row): bool => touches($row, $superAdmin));

    expect($hidden->pluck('event')->all())->toContain('updated', 'settings_updated', 'login_failed', 'level_changed')
        ->and(ImmutableActivity::query()->visibleTo($viewer)->pluck('id')->all())->toEqualCanonicalizing($all->diff($hidden)->pluck('id')->all())
        ->and(ImmutableActivity::query()->visibleTo($viewer)->where('event', 'login_locked')->exists())->toBeTrue()
        ->and(ImmutableActivity::query()->visibleTo($superAdmin)->count())->toBe($all->count());
});

it('gives a record history newest first, 20 per page, with the same rule', function (): void {
    $superAdmin = superAdminUser();
    $viewer = makeUser('Viewer');
    $item = DemoItem::query()->create(['name' => 'v0']);
    DemoItem::query()->create(['name' => 'other']);

    foreach (range(1, 22) as $version) {
        $item->update(['name' => "v{$version}"]);
    }
    actingAs($superAdmin);
    $item->update(['name' => 'by-sa']);

    $page = $item->activityHistory($viewer);
    expect($page->total())->toBe(23)
        ->and($page->perPage())->toBe(20)
        ->and($page->items()[0]->attribute_changes?->get('attributes'))->toBe(['name' => 'v22'])
        ->and($item->activityHistory($superAdmin)->total())->toBe(24);
});
