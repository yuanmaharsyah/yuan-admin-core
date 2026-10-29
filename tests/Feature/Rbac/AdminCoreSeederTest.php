<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Yuan\AdminCore\Database\Seeders\AdminCoreSeeder;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Permissions\PermissionMatrix;
use Yuan\AdminCore\Tests\Fixtures\Models\User;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(fn () => config(['admin-core.seed.password' => 'Rahasia12345']));

/**
 * @return array<string, int>
 */
function rowCounts(): array
{
    return collect(['users', 'roles', 'permissions', 'role_has_permissions', 'model_has_roles', 'model_has_permissions'])
        ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])
        ->all();
}

it('seeds the levels, two Super Admins and an Operator that views the project modules, and is idempotent', function (): void {
    seed(AdminCoreSeeder::class);
    $first = rowCounts();
    seed(AdminCoreSeeder::class);

    $operator = Level::query()->where('name', 'operator')->sole();
    expect(rowCounts())->toBe($first)
        ->and($first)->toMatchArray(['users' => 2, 'roles' => 2, 'permissions' => 5, 'model_has_permissions' => 0])
        ->and(User::query()->get()->every(fn (User $user): bool => $user->isSuperAdmin() && $user->is_active))->toBeTrue()
        ->and($operator->permissions()->orderBy('name')->pluck('name')->all())->toBe(['demo.view', 'hidden.view']); // notes is a core module: not for Operator
});

it('keeps matrix edits and passwords on a re-seed', function (): void {
    seed(AdminCoreSeeder::class);
    $operator = Level::query()->where('name', 'operator')->sole();
    app(PermissionMatrix::class)->save($operator, ['demo' => ['view', 'create']]);
    User::query()->where('username', 'superadmin')->sole()->update(['password' => 'GantiBaru12345']);

    seed(AdminCoreSeeder::class);

    expect($operator->permissions()->orderBy('name')->pluck('name')->all())->toBe(['demo.create', 'demo.view'])
        ->and(Hash::check('GantiBaru12345', User::query()->where('username', 'superadmin')->sole()->getAuthPassword()))->toBeTrue();
});

it('prints a random password once per account when none is set', function (): void {
    config(['admin-core.seed.password' => null]);

    command('db:seed', ['--class' => AdminCoreSeeder::class])
        ->expectsOutputToContain('Password for [superadmin]:')
        ->expectsOutputToContain('Password for [superadmin2]:')
        ->assertSuccessful();
});

it('refuses to make up a password in production', function (): void {
    config(['admin-core.seed.password' => null]);
    app()->detectEnvironment(fn (): string => 'production');

    expect(fn () => app()->call([app(AdminCoreSeeder::class), 'run']))->toThrow(RuntimeException::class, 'admin:create-superadmin')
        ->and(User::query()->count())->toBe(0);
});
