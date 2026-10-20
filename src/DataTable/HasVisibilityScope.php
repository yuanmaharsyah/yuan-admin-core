<?php

declare(strict_types=1);

namespace Yuan\AdminCore\DataTable;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

/**
 * The data-scope hook (spec §7): every list and show query calls
 * `->visibleTo($user)`. It does nothing by default; a model that needs an
 * "own unit only" rule overrides `applyVisibility()`. Every module model uses
 * this trait (contract test), and `ModuleAssertions::assertScopedToUser()`
 * checks that a route actually calls it.
 */
trait HasVisibilityScope
{
    /**
     * @param  Builder<static>  $query
     */
    final public function scopeVisibleTo(Builder $query, ?Authenticatable $user): void
    {
        VisibilityScope::applied(static::class, $user);
        $this->applyVisibility($query, $user);
    }

    /**
     * @param  Builder<static>  $query
     */
    protected function applyVisibility(Builder $query, ?Authenticatable $user): void {}
}
