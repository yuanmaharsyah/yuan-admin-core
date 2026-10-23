<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Users\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Events\UserCreated;
use Yuan\AdminCore\Events\UserDeactivated;
use Yuan\AdminCore\Modules\Users\Data\StoreUserData;
use Yuan\AdminCore\Modules\Users\Data\UpdateUserData;
use Yuan\AdminCore\Modules\Users\Models\User;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Users\UserExtension;

/**
 * Writes to user accounts (spec §6.3). Each runs in one transaction with its
 * audit rows; HasLevel guards the last active Super Admin and revokes
 * sessions on deactivation, level and password changes.
 */
final class UserAccounts
{
    /**
     * @return class-string<User>
     */
    public static function model(): string
    {
        /** @var class-string<User> */
        return config()->string('admin-core.models.user');
    }

    public function create(StoreUserData $data): User
    {
        $model = self::model();

        $user = DB::transaction(function () use ($data, $model): User {
            $user = new $model([...self::attributes($data->name, $data->username, $data->email, $data->is_active, $data->extra), 'password' => $data->password]);
            $user->forceFill(['must_change_password' => true])->save();
            $user->assignLevel(Level::query()->findOrFail($data->level_id));

            return $user;
        });

        DB::afterCommit(fn () => UserCreated::dispatch($user));

        return $user;
    }

    /**
     * @throws ValidationException When the signed-in user changes their own level or status.
     */
    public function update(User $user, UpdateUserData $data, ?Authenticatable $actor): void
    {
        $level = Level::query()->findOrFail($data->level_id);
        $self = $actor instanceof Model && $actor->is($user);

        if ($self && (! $level->is($user->level()) || ! $data->is_active)) {
            throw ValidationException::withMessages(['level_id' => __('Level dan status akun sendiri tidak dapat diubah.')]);
        }

        DB::transaction(function () use ($user, $data, $level): void {
            $wasActive = $user->is_active;
            $user->updateUnlessStale(self::attributes($data->name, $data->username, $data->email, $data->is_active, $data->extra), $data->lock_version);
            $user->assignLevel($level);

            if ($wasActive !== $user->is_active) {
                self::auditActive($user);
            }
        });
    }

    /**
     * Nobody deactivates, resets or deletes their own account from Users
     * (spec §6.3), Super Admin included.
     *
     * @throws AuthorizationException
     */
    public static function refuseSelf(User $user, ?Authenticatable $actor): void
    {
        if ($actor instanceof Model && $actor->is($user)) {
            throw new AuthorizationException(__('Akun sendiri tidak dapat diubah dari sini.'));
        }
    }

    public function setActive(User $user, bool $active): void
    {
        if ($user->is_active === $active) {
            return;
        }

        DB::transaction(function () use ($user, $active): void {
            $user->update(['is_active' => $active]);
            self::auditActive($user);
        });
    }

    /**
     * Sets a new password (generated when null) that must be changed at the
     * next sign-in, ends the user's sessions, and returns it to show once.
     */
    public function resetPassword(User $user, ?string $password): string
    {
        $password ??= self::temporaryPassword();

        DB::transaction(function () use ($user, $password): void {
            $user->forceFill(['password' => $password, 'must_change_password' => true])->save();
            Audit::record(AuditEvent::PasswordReset, $user, ['by_admin' => true], 'users');
        });

        return $password;
    }

    /**
     * At least 12 characters (more if the password rules ask for more): upper
     * and lower case and digits, no look-alikes (0/O, 1/l/I).
     */
    public static function temporaryPassword(): string
    {
        $each = (int) ceil(max(12, config()->integer('admin-core.passwords.min', 10)) / 3);
        $pick = fn (string $set): string => implode('', array_map(fn (): string => $set[random_int(0, strlen($set) - 1)], range(1, $each)));

        return str_shuffle($pick('ABCDEFGHJKLMNPQRSTUVWXYZ').$pick('abcdefghijkmnopqrstuvwxyz').$pick('23456789'));
    }

    private static function auditActive(User $user): void
    {
        if ($user->is_active) {
            Audit::record(AuditEvent::UserActivated, $user, module: 'users');
        } else {
            Audit::record(AuditEvent::UserDeactivated, $user, module: 'users');
            DB::afterCommit(fn () => UserDeactivated::dispatch($user));
        }
    }

    /**
     * @param  array<string, string|int|float|null>  $extra
     * @return array<string, mixed>
     */
    private static function attributes(string $name, ?string $username, string $email, bool $active, array $extra): array
    {
        return [
            'name' => $name,
            'username' => $username === null || $username === '' ? null : Str::lower($username),
            'email' => Str::lower($email),
            'is_active' => $active,
            ...array_intersect_key($extra, array_flip(UserExtension::names())),
        ];
    }
}
