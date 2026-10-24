<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The "Riwayat" of a record (spec §7.1): its events, newest first, 20 per
 * page (`history_page`), with the SA visibility rule. Show pages send it
 * through `ActivityHistory::prop()`, which also checks who may see the tab.
 *
 * @mixin Model
 */
trait HasActivityHistory
{
    /**
     * @return LengthAwarePaginator<int, ImmutableActivity>
     */
    public function activityHistory(Authenticatable $viewer): LengthAwarePaginator
    {
        return ActivityHistory::paginate($this, $viewer);
    }
}
