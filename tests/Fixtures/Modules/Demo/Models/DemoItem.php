<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Yuan\AdminCore\Audit\HasActivityHistory;
use Yuan\AdminCore\Audit\LogsModuleActivity;
use Yuan\AdminCore\Concurrency\PreventsStaleUpdates;
use Yuan\AdminCore\Data\DataTableFilterData;
use Yuan\AdminCore\Data\OptionData;
use Yuan\AdminCore\DataTable\DataTableModel;
use Yuan\AdminCore\DataTable\HasDataTable;
use Yuan\AdminCore\DataTable\HasVisibilityScope;

/**
 * @property string|null $name
 * @property Carbon|null $updated_at
 */
final class DemoItem extends Model implements DataTableModel
{
    use HasActivityHistory, HasDataTable, HasVisibilityScope, LogsModuleActivity, PreventsStaleUpdates, SoftDeletes;

    protected $table = 'demo_items';

    protected $fillable = ['name', 'nik', 'no_hp', 'secret', 'pin'];

    protected $hidden = ['pin'];

    /**
     * @return list<string>
     */
    public function dataTableSearch(): array
    {
        return ['name', 'nik'];
    }

    /**
     * @return list<string>
     */
    public function dataTableSorts(): array
    {
        return ['name', 'created_at'];
    }

    /**
     * @return list<DataTableFilterData>
     */
    public function dataTableFilters(): array
    {
        return [new DataTableFilterData('status', 'Status', [new OptionData('draft', 'Draf'), new OptionData('paid', 'Lunas')])];
    }
}
