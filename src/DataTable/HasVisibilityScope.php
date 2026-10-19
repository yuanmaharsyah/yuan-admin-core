<?php

declare(strict_types=1);

namespace Yuan\AdminCore\DataTable;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

/**
 * The data-scope hook (spec §7): every list and show query calls
 * `->visibleTo($user)`. It does nothing by default; a model that needs an
 * "own unit only" rule overrides `scopeVisibleTo()`. Every module model uses
 * this trait (contract test).
 */
trait HasVisibilityScope
{
    /**
     * @param  Builder<static>  $query
     */
    public function scopeVisibleTo(Builder $query, ?Authenticatable $user): void {}
}
