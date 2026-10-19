<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Permissions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Http\Middleware\EnsureUserIsActive;

/**
 * Exactly one Level per user and no direct permissions (spec §6.2, D5), plus
 * the user guard rails of spec §6.3: there is always an active Super Admin,
 * and deactivating, changing the level or changing the password revokes the
 * user's other sessions.
 *
 * @property bool $is_active
 * @property Carbon|null $sessions_invalidated_at
 *
 * @mixin Model
 */
trait HasLevel
{
    use HasRoles;

    public static function bootHasLevel(): void
    {
        static::updating(function (self $user): void {
            $deactivated = $user->isDirty('is_active') && ! $user->is_active;

            if ($deactivated && $user->isLastActiveSuperAdmin()) {
                throw GuardRailViolation::lastSuperAdmin();
            }

            if ($deactivated || $user->isDirty('password')) {
                $user->forceFill(['sessions_invalidated_at' => now(), 'remember_token' => Str::random(60)]);
            }
        });

        static::updated(function (self $user): void {
            if ($user->wasChanged('sessions_invalidated_at')) {
                $user->afterSessionsRevoked();
            }
        });
    }

    /**
     * Checked here, not in a `deleting` listener: spatie's own listener detaches
     * the roles first, so a refused delete would still lose the level.
     *
     * @throws GuardRailViolation When this would leave no active Super Admin.
     */
    public function delete(): ?bool
    {
        if ($this->isLastActiveSuperAdmin()) {
            throw GuardRailViolation::lastSuperAdmin();
        }

        return parent::delete();
    }

    public function level(): ?Level
    {
        $level = $this->roles->first();

        return $level instanceof Level ? $level : null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->level()?->is_protected === true;
    }

    /**
     * Replaces the user's level; writes one batched `level_changed` and revokes
     * the user's other sessions.
     *
     * @throws GuardRailViolation When this would leave no active Super Admin.
     */
    public function assignLevel(Level $level): void
    {
        $previous = $this->level();

        if ($previous?->is($level)) {
            return;
        }

        if ($previous?->is_protected && $this->isLastActiveSuperAdmin()) {
            throw GuardRailViolation::lastSuperAdmin();
        }

        DB::transaction(fn () => Audit::batch(function () use ($level, $previous): void {
            $this->roles()->sync([$level->id]);
            $this->unsetRelation('roles');
            $this->forgetCachedPermissions();

            Audit::record(AuditEvent::LevelChanged, $this, ['from' => $previous?->name, 'to' => $level->name], 'users');

            if ($previous !== null) {
                $this->forceFill(['sessions_invalidated_at' => now(), 'remember_token' => Str::random(60)])->saveQuietly();
                $this->afterSessionsRevoked();
            }
        }));
    }

    public function assignRole(mixed ...$roles): static
    {
        throw GuardRailViolation::directPermissions();
    }

    public function removeRole(mixed ...$role): static
    {
        throw GuardRailViolation::directPermissions();
    }

    public function syncRoles(mixed ...$roles): static
    {
        throw GuardRailViolation::directPermissions();
    }

    public function givePermissionTo(mixed ...$permissions): static
    {
        throw GuardRailViolation::directPermissions();
    }

    public function syncPermissions(mixed ...$permissions): static
    {
        throw GuardRailViolation::directPermissions();
    }

    /**
     * Whether this user is the only active Super Admin left.
     */
    private function isLastActiveSuperAdmin(): bool
    {
        return $this->isSuperAdmin()
            && (bool) $this->getOriginal('is_active')
            && static::query()
                ->whereKeyNot($this->getKey())
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('is_protected', true))
                ->doesntExist();
    }

    /**
     * Keeps the current session when the user revoked their own sessions, and
     * deletes the stored sessions when the driver is `database` (cleanup only:
     * EnsureUserIsActive enforces revocation on any driver).
     */
    private function afterSessionsRevoked(): void
    {
        $session = request()->hasSession() ? request()->session() : null;

        if ($session !== null && Auth::id() === $this->getKey()) {
            $session->put(EnsureUserIsActive::LOGIN_TIME, $this->sessions_invalidated_at?->getTimestamp());
        }

        if (config('session.driver') === 'database') {
            DB::table(config()->string('session.table', 'sessions'))
                ->where('user_id', $this->getKey())
                ->when($session !== null, fn ($query) => $query->where('id', '!=', $session?->getId()))
                ->delete();
        }
    }
}
