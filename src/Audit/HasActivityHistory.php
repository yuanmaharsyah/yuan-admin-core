<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The "Riwayat" of a record (spec §7.1): its events, newest first, 20 per
 * page, with the SA visibility rule. Who may see the tab is the page's check.
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
        return ImmutableActivity::query()->forSubject($this)->visibleTo($viewer)->latest('id')->paginate(20);
    }
}
