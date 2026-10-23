<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Permissions;

use Spatie\Permission\Models\Role;
use Yuan\AdminCore\Audit\LogsModuleActivity;
use Yuan\AdminCore\Concurrency\PreventsStaleUpdates;

/**
 * A Level is a spatie Role (spec §5, D5). Super Admin is the level with
 * `is_protected`; it is immutable, and a level with users can't be deleted.
 *
 * @property int $id
 * @property string $name
 * @property string $label
 * @property string|null $description
 * @property bool $is_protected
 * @property int $lock_version
 */
final class Level extends Role
{
    use LogsModuleActivity, PreventsStaleUpdates;

    public const string SUPER_ADMIN = 'super-admin';

    /** @var array<string, mixed> */
    protected $attributes = ['lock_version' => 0];

    protected $fillable = ['name', 'guard_name', 'label', 'description'];

    /**
     * The protected level, created on first use.
     */
    public static function superAdmin(): self
    {
        $level = self::query()->where('is_protected', true)->first();

        if ($level === null) {
            $level = new self(['name' => self::SUPER_ADMIN, 'label' => 'Super Admin', 'description' => 'Akses penuh ke seluruh sistem.']);
            $level->forceFill(['is_protected' => true])->save();
        }

        return $level;
    }

    /**
     * Checked here, not in a `deleting` listener: spatie's own listener detaches
     * the users and permissions first, so a refused delete would still lose them.
     *
     * @throws GuardRailViolation For the protected level or a level with users.
     */
    public function delete(): ?bool
    {
        if ($this->is_protected) {
            throw GuardRailViolation::protectedLevel();
        }

        if ($this->users()->exists()) {
            throw GuardRailViolation::levelInUse();
        }

        return parent::delete();
    }

    public function auditLabel(): string
    {
        return $this->label;
    }

    protected function auditLogName(): string
    {
        return 'levels';
    }

    protected function casts(): array
    {
        return ['is_protected' => 'boolean', 'lock_version' => 'integer'];
    }

    protected static function booted(): void
    {
        self::updating(function (self $level): void {
            if ($level->getOriginal('is_protected')) {
                throw GuardRailViolation::protectedLevel();
            }
        });
    }
}
