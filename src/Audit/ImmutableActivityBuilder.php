<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use Illuminate\Database\Eloquent\Builder;

/**
 * Blocks the Eloquent query writes that skip model events.
 *
 * @extends Builder<ImmutableActivity>
 */
final class ImmutableActivityBuilder extends Builder
{
    public function update(array $values): int
    {
        throw new ImmutableAuditException;
    }

    public function delete(): mixed
    {
        throw new ImmutableAuditException;
    }

    public function forceDelete(): mixed
    {
        throw new ImmutableAuditException;
    }

    public function increment($column, $amount = 1, array $extra = []): int
    {
        throw new ImmutableAuditException;
    }

    public function decrement($column, $amount = 1, array $extra = []): int
    {
        throw new ImmutableAuditException;
    }

    /**
     * @param  array<mixed>  $values
     * @param  array<mixed>|string  $uniqueBy
     * @param  array<mixed>|null  $update
     */
    public function upsert(array $values, $uniqueBy, $update = null): int
    {
        throw new ImmutableAuditException;
    }
}
