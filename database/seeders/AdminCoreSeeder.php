<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Permissions\PermissionSynchronizer;

/**
 * Idempotent: syncs permissions, ensures the Super Admin and Operator levels
 * and two Super Admin accounts. Existing rows are never changed, so a re-seed
 * keeps every matrix edit and password.
 */
final class AdminCoreSeeder extends Seeder
{
    private const array SUPER_ADMINS = [
        ['name' => 'Super Admin', 'username' => 'superadmin', 'email' => 'superadmin@example.test'],
        ['name' => 'Super Admin 2', 'username' => 'superadmin2', 'email' => 'superadmin2@example.test'],
    ];

    public function run(PermissionSynchronizer $synchronizer): void
    {
        $synchronizer->sync();
        $superAdmin = Level::superAdmin();

        $operator = Level::query()->firstOrCreate(['name' => 'operator'], ['label' => 'Operator', 'description' => 'Hanya dapat melihat data.']);

        if ($operator->wasRecentlyCreated) {
            $operator->syncPermissions(Permission::query()->where('action', 'view')->pluck('name')->all());
        }

        /** @var class-string<Model> $model */
        $model = config()->string('auth.providers.users.model');

        foreach (self::SUPER_ADMINS as $account) {
            if ($model::query()->withoutGlobalScopes()->where('username', $account['username'])->exists()) {
                continue;
            }

            $user = new $model;

            if (! method_exists($user, 'assignLevel')) {
                throw new RuntimeException("The user model [{$model}] must use the HasLevel trait.");
            }

            $user->forceFill([...$account, 'password' => $this->password($account['username']), 'is_active' => true])->save();
            $user->assignLevel($superAdmin);
        }
    }

    /**
     * ADMIN_SEED_PASSWORD, or a random one shown once on the console (never
     * logged). Production refuses to make one up.
     */
    private function password(string $username): string
    {
        $password = config('admin-core.seed.password');

        if (is_string($password) && $password !== '') {
            return $password;
        }

        if (app()->isProduction()) {
            throw new RuntimeException('Set ADMIN_SEED_PASSWORD before seeding in production, or skip the seeder and run `php artisan admin:create-superadmin`.');
        }

        $password = Str::password(20);
        $this->command?->warn("Password for [{$username}]: {$password}  (shown once; change it after logging in)");

        return $password;
    }
}
