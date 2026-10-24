<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\OptionalProp;
use Yuan\AdminCore\Data\Audit\ActivityHistoryData;

/**
 * The Riwayat tab's prop (frontend-behavior §8.3a): `'history' =>
 * ActivityHistory::prop($record, $request->user())` on a Show page. Optional,
 * so it is sent only when the tab asks for it; null when the viewer lacks
 * `audit-log.view` (the page checks `{slug}.view` itself).
 */
final class ActivityHistory
{
    public static function prop(Model $record, ?Authenticatable $viewer): ?OptionalProp
    {
        if (! $viewer instanceof Authorizable || ! $viewer->can('audit-log.view')) {
            return null;
        }

        return Inertia::optional(function () use ($record, $viewer): ActivityHistoryData {
            $page = self::paginate($record, $viewer);

            return new ActivityHistoryData(
                items: app(ActivityPresenter::class)->items($page->getCollection(), $viewer, withDiff: true),
                total: $page->total(),
                next_page: $page->hasMorePages() ? $page->currentPage() + 1 : null,
            );
        });
    }

    /**
     * The record's events, newest first, 20 per page (`history_page`), with
     * the SA visibility rule.
     *
     * @return LengthAwarePaginator<int, ImmutableActivity>
     */
    public static function paginate(Model $record, Authenticatable $viewer): LengthAwarePaginator
    {
        return ImmutableActivity::query()->forSubject($record)->visibleTo($viewer)->latest('id')->paginate(20, pageName: 'history_page');
    }
}
