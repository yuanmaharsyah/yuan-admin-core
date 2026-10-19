<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\AssertionFailedError;
use Spatie\Permission\Models\Permission;
use Yuan\AdminCore\Database\Seeders\AdminCoreSeeder;
use Yuan\AdminCore\Testing\ContractAssertions;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

it('passes after the full seed', function (): void {
    seed(AdminCoreSeeder::class);

    ContractAssertions::assertNoDirectPermissions();
});

it('fails when a user holds a permission directly', function (): void {
    syncPermissions();
    $user = makeUser();
    // givePermissionTo() throws, so write the pivot row the way a bypass would.
    DB::table('model_has_permissions')->insert(['permission_id' => Permission::query()->value('id'), 'model_type' => $user->getMorphClass(), 'model_id' => $user->id]);

    expect(fn () => ContractAssertions::assertNoDirectPermissions())->toThrow(AssertionFailedError::class, 'model_has_permissions has 1 row(s)');
});
