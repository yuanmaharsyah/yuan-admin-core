<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Users\Exports;

use Illuminate\Database\Eloquent\Model;
use Yuan\AdminCore\DataTable\DataTableBuilder;
use Yuan\AdminCore\Exports\ModuleExport;
use Yuan\AdminCore\Modules\Users\Models\User;
use Yuan\AdminCore\Modules\Users\Services\UserAccounts;

/**
 * The user list as a spreadsheet, current search, filters and sort applied;
 * Super Admin accounts only for Super Admin (`visibleTo()`).
 *
 * @extends ModuleExport<User>
 */
final class UsersExport extends ModuleExport
{
    /**
     * @return DataTableBuilder<User>
     */
    protected function baseQuery(): DataTableBuilder
    {
        return UserAccounts::model()::query()->visibleTo($this->user)->with('roles');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [__('Nama'), __('Username'), __('Email'), __('Level'), __('Status'), __('Login terakhir')];
    }

    /**
     * @param  User  $row
     * @return list<string|null>
     */
    protected function row(Model $row): array
    {
        return [
            $row->name,
            $row->username,
            $row->email,
            $row->level()?->label,
            $row->is_active ? __('Aktif') : __('Nonaktif'),
            $row->last_login_at?->format('Y-m-d H:i'),
        ];
    }
}
