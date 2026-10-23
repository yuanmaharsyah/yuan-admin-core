<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Users\Models;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Yuan\AdminCore\Audit\HasActivityHistory;
use Yuan\AdminCore\Audit\LogsModuleActivity;
use Yuan\AdminCore\Concurrency\PreventsStaleUpdates;
use Yuan\AdminCore\Data\DataTableFilterData;
use Yuan\AdminCore\Data\OptionData;
use Yuan\AdminCore\DataTable\DataTableModel;
use Yuan\AdminCore\DataTable\HasDataTable;
use Yuan\AdminCore\DataTable\HasVisibilityScope;
use Yuan\AdminCore\Permissions\HasLevel;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Users\UserExtension;

/**
 * The core user (spec §2.2, §6). Projects extend it (`App\Models\User`, set in
 * `admin-core.models.user` and `auth.providers.users.model`) and add columns
 * for their form extension. Super Admin accounts are invisible to everyone
 * else through `visibleTo()`, so non-SA users get a 404 on them (§6.3).
 *
 * @property int $id
 * @property string $name
 * @property string|null $username
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property bool $is_active
 * @property bool $must_change_password
 * @property Carbon|null $last_login_at
 * @property int $session_version
 * @property int $lock_version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class User extends Authenticatable implements DataTableModel
{
    use HasActivityHistory, HasDataTable, HasLevel, HasVisibilityScope, LogsModuleActivity, Notifiable, PreventsStaleUpdates, SoftDeletes;

    protected $table = 'users';

    /** @var list<string> */
    protected $fillable = ['name', 'username', 'email', 'password', 'is_active'];

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token'];

    /** @var array<string, mixed> */
    protected $attributes = ['is_active' => true, 'must_change_password' => false, 'lock_version' => 0];

    /**
     * The core columns plus the project's extra fields (UserFormExtension).
     *
     * @return list<string>
     */
    public function getFillable(): array
    {
        return array_values(array_unique([...parent::getFillable(), ...UserExtension::names()]));
    }

    public function auditLabel(): string
    {
        return $this->name;
    }

    public function dataTableSearch(): array
    {
        return ['name', 'username', 'email'];
    }

    public function dataTableSorts(): array
    {
        return ['name', 'last_login_at'];
    }

    public function dataTableFilters(): array
    {
        $viewer = auth()->user();
        $levels = Level::query()
            ->when(! self::isSuperAdminViewer($viewer), fn (Builder $query) => $query->where('is_protected', false))
            ->orderBy('label')
            ->get();

        return [
            new DataTableFilterData('level', __('Level'), array_values($levels->map(fn (Level $level): OptionData => new OptionData((string) $level->id, $level->label))->all()), 'roles.id'),
            new DataTableFilterData('status', __('Status'), [new OptionData('1', __('Aktif')), new OptionData('0', __('Nonaktif'))], 'is_active'),
        ];
    }

    public static function isSuperAdminViewer(?AuthenticatableContract $viewer): bool
    {
        return $viewer !== null && Gate::forUser($viewer)->allows('admin-core.superadmin');
    }

    /**
     * Super Admin accounts are visible to Super Admin only (spec §6.3).
     *
     * @param  Builder<static>  $query
     */
    protected function applyVisibility(Builder $query, ?AuthenticatableContract $user): void
    {
        if (! self::isSuperAdminViewer($user)) {
            $query->whereDoesntHave('roles', fn (Builder $roles) => $roles->where('is_protected', true));
        }
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'session_version' => 'integer',
            'lock_version' => 'integer',
        ];
    }
}
