<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\AssertionFailedError;
use Yuan\AdminCore\Audit\Masker;
use Yuan\AdminCore\Testing\ModuleAssertions;
use Yuan\AdminCore\Tests\Fixtures\Models\User;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Route::get('/login', fn (): string => 'login')->name('login');
    Route::middleware(['web', 'auth', 'active', 'permission:demo.create'])->post('/admin/demo/create', fn (): string => 'created');
    Route::get('/public', fn (): string => 'public');
});

it('signs in a non-SA user holding exactly the given permissions', function (): void {
    $user = ModuleAssertions::actingAsLevelWith(['demo.view']);
    $root = ModuleAssertions::actingAsSuperAdmin();
    assert($user instanceof User && $root instanceof User);

    expect(auth()->user()?->is($root))->toBeTrue()
        ->and($user->getAttribute('is_active'))->toBeTrue()
        ->and($user->level()?->permissions()->pluck('name')->all())->toBe(['demo.view'])
        ->and($user->isSuperAdmin())->toBeFalse()
        ->and($root->isSuperAdmin())->toBeTrue();
});

it('checks the guest redirect', function (): void {
    ModuleAssertions::assertGuestRedirected('get', '/admin/demo');

    expect(fn () => ModuleAssertions::assertGuestRedirected('get', '/public'))->toThrow(AssertionFailedError::class);
});

it('checks 403 with every other permission of the module', function (): void {
    ModuleAssertions::assertForbiddenWithout('demo.create', 'post', '/admin/demo/create');

    expect(fn () => ModuleAssertions::assertForbiddenWithout('demo.view', 'get', '/admin/demo'))->toThrow(AssertionFailedError::class)
        ->and(fn () => ModuleAssertions::assertForbiddenWithout('demo.publish', 'get', '/admin/demo'))->toThrow(LogicException::class, 'not a declared module action');
});

it('finds the audit row and checks masked fields', function (): void {
    $item = DemoItem::query()->create(['name' => 'A', 'nik' => '3201234567890001', 'no_hp' => '081234567890', 'secret' => 'kunci']);

    expect(ModuleAssertions::assertAudited($item, 'created', masked: ['nik', 'no_hp', 'secret'])->event)->toBe('created')
        ->and(fn () => ModuleAssertions::assertAudited($item, 'updated'))->toThrow(AssertionFailedError::class, 'No [updated] audit row')
        ->and(fn () => ModuleAssertions::assertAudited($item, 'created', masked: ['alamat']))->toThrow(AssertionFailedError::class, '[alamat] is not in the [created] row')
        ->and(fn () => ModuleAssertions::assertAudited($item, 'created', masked: ['name']))->toThrow(AssertionFailedError::class, '[name] is not in audit.masked_fields');
});

it('fails when a masked field was stored raw or an omitted one stored at all', function (string $rule, string $message): void {
    app(Masker::class); // the row is written with the rules in place before the change below
    $item = DemoItem::query()->create(['name' => 'Budi Santoso Wijaya']);
    config(['admin-core.audit.masked_fields.name' => $rule]);
    app()->forgetInstance(Masker::class);

    expect(fn () => ModuleAssertions::assertAudited($item, 'created', masked: ['name']))->toThrow(AssertionFailedError::class, $message);
})->with([
    'partial' => ['partial', '[name] is stored unmasked'],
    'full' => ['full', '[name] is stored unmasked'],
    'omit' => ['omit', '[name] must never be stored'],
]);

it('checks that a row is hidden from non-SA viewers', function (): void {
    $root = superAdminUser();
    $staff = makeUser('Staf');
    syncPermissions();
    $staff->assignLevel(levelWith(['demo.view']));

    ModuleAssertions::assertNotVisibleToNonSuperAdmin(ModuleAssertions::assertAudited($root, 'level_changed'));

    expect(fn () => ModuleAssertions::assertNotVisibleToNonSuperAdmin(ModuleAssertions::assertAudited($staff, 'level_changed')))
        ->toThrow(AssertionFailedError::class, 'is visible to a non-Super Admin');
});
