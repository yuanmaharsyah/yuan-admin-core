<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Concurrency;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Stale-update protection (spec §7) with an integer `lock_version` column
 * (unsigned, default 0; the model also sets `$attributes = ['lock_version' => 0]`
 * so a new instance knows its version without a reload). Every update of the model raises it by one in the
 * same UPDATE statement; the edit form carries the version it loaded, and
 * `updateUnlessStale()` refuses the save with a 409 when the row's version
 * moved on. The compare and the save run under a row lock in one
 * transaction, so two saves of the same version never both pass, even
 * within the same second.
 */
trait PreventsStaleUpdates
{
    public static function bootPreventsStaleUpdates(): void
    {
        static::updating(function (Model $model): void {
            if (! $model->isDirty('lock_version')) {
                $model->setAttribute('lock_version', (int) $model->getOriginal('lock_version') + 1);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws StaleUpdateException
     */
    public function updateUnlessStale(array $attributes, int $loadedVersion): void
    {
        DB::transaction(function () use ($attributes, $loadedVersion): void {
            $current = static::query()->withoutGlobalScopes()->whereKey($this->getKey())->lockForUpdate()->value('lock_version');

            if ((int) $current !== $loadedVersion) {
                throw new StaleUpdateException;
            }

            $this->fill($attributes)->forceFill(['lock_version' => $loadedVersion + 1])->save();
        });
    }
}
