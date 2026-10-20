<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Concurrency;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Stale-update protection (spec §7): the update Data carries the `updated_at`
 * the form was loaded with, and `updateUnlessStale()` refuses the save with a
 * 409 when the row changed since. The check and the save run under a row lock
 * in one transaction. Timestamps have second precision, so two saves within
 * the same second are not told apart.
 */
trait PreventsStaleUpdates
{
    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws StaleUpdateException
     */
    public function updateUnlessStale(array $attributes, string $loadedAt): void
    {
        DB::transaction(function () use ($attributes, $loadedAt): void {
            $current = static::query()->withoutGlobalScopes()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $updatedAt = $current->getAttribute($this->getUpdatedAtColumn() ?? 'updated_at');

            if (! $updatedAt instanceof DateTimeInterface || $updatedAt->getTimestamp() !== Carbon::parse($loadedAt)->getTimestamp()) {
                throw new StaleUpdateException;
            }

            $this->fill($attributes)->save();
        });
    }
}
