<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\AuditLog\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use LogicException;
use Yuan\AdminCore\Audit\AuditCategory;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Modules\AuditLog\Data\AuditCountData;
use Yuan\AdminCore\Modules\AuditLog\Data\AuditFilterData;
use Yuan\AdminCore\Modules\AuditLog\Data\AuditSummaryData;

/**
 * The Log Aktivitas summary (spec §7, AuditSummary): grouped queries over
 * the range, user and module, with the SA visibility rule.
 * Cached for 5 minutes per (range, user, module, viewer-is-SA): every
 * non-SA viewer sees the same rows, so the viewer's id isn't in the key.
 *
 * @phpstan-type Figures array{events: array<string, int>, days: array<string, int>, rows_downloaded: int}
 */
final class AuditSummary
{
    public const int WEEKLY_ABOVE_DAYS = 60;

    public function for(AuditFilterData $filters, Authenticatable $viewer): AuditSummaryData
    {
        [$from, $to] = $filters->period();
        $superAdmin = method_exists($viewer, 'isSuperAdmin') && $viewer->isSuperAdmin();
        $key = 'admin-core:audit-summary:'.implode('|', [$from->toDateString(), $to->toDateString(), $filters->user() ?? '-', $filters->module() ?? '-', $superAdmin ? 'sa' : 'other']);

        return Cache::remember($key, 300, fn (): AuditSummaryData => $this->compute($filters, $viewer, $superAdmin, $from, $to));
    }

    private function compute(AuditFilterData $filters, Authenticatable $viewer, bool $superAdmin, CarbonImmutable $from, CarbonImmutable $to): AuditSummaryData
    {
        if ($superAdmin) {
            $figures = self::figures(AuditQuery::filter(ImmutableActivity::query(), $filters));
        } else {
            // visibleTo() as NOT IN subqueries makes every row in the range
            // pay for the SA check. Counting everything (created_at index) and
            // subtracting the few SA rows (causer and subject indexes) is the
            // same result; the ids are read now, so a level change applies at once.
            $superAdmins = ImmutableActivity::superAdminIds();
            $all = self::figures(AuditQuery::filter(ImmutableActivity::query(), $filters));
            $hidden = self::figures(AuditQuery::filter(ImmutableActivity::query()->involvingUsers($superAdmins), $filters));
            $figures = self::subtract($all, $hidden);
        }

        $byEvent = $figures['events'];
        $count = fn (AuditEvent $event): int => $byEvent[$event->value] ?? 0;
        $byType = array_fill_keys(array_map(fn (AuditCategory $category): string => $category->value, AuditCategory::cases()), 0);

        foreach ($byEvent as $event => $total) {
            $byType[AuditCategory::of((string) $event)->value] += $total;
        }

        $weekly = $from->diffInDays($to) + 1 > self::WEEKLY_ABOVE_DAYS;

        return new AuditSummaryData(
            total: array_sum($byEvent),
            active_users: self::activeUsers($filters, $viewer),
            failed_logins: $count(AuditEvent::LoginFailed),
            downloads: $count(AuditEvent::Exported),
            rows_downloaded: $figures['rows_downloaded'],
            permission_changes: $count(AuditEvent::PermissionsUpdated),
            logins: $count(AuditEvent::Login),
            by_type: array_map(
                fn (AuditCategory $category): AuditCountData => new AuditCountData($category->value, $category->label(), $byType[$category->value]),
                AuditCategory::cases(),
            ),
            by_day: self::buckets($figures['days'], $from, $to, $weekly),
            bucket: $weekly ? 'week' : 'day',
            from: $from->toDateString(),
            to: $to->toDateString(),
        );
    }

    /**
     * Every additive figure of the query: rows per event and per day (one
     * grouped query) and the exported row total.
     *
     * @param  Builder<ImmutableActivity>  $query
     * @return Figures
     */
    private static function figures(Builder $query): array
    {
        $events = [];
        $days = [];

        foreach ((clone $query)->toBase()->selectRaw('created_on as day, event, count(*) as total')->groupBy('created_on', 'event')->get() as $row) {
            $events[(string) $row->event] = ($events[(string) $row->event] ?? 0) + (int) $row->total;
            $days[(string) $row->day] = ($days[(string) $row->day] ?? 0) + (int) $row->total;
        }

        return [
            'events' => $events,
            'days' => $days,
            'rows_downloaded' => (int) (clone $query)->where('event', AuditEvent::Exported->value)->sum('properties->meta->rows'),
        ];
    }

    /**
     * Distinct user causers with a visible row: one EXISTS per user on the
     * (causer_id, created_at) index, which stops at the first visible row.
     * Users no longer in the table (force-deleted) don't count.
     */
    private static function activeUsers(AuditFilterData $filters, Authenticatable $viewer): int
    {
        $users = app(config()->string('auth.providers.users.model'));

        if (! $users instanceof Model) {
            throw new LogicException('auth.providers.users.model must be an Eloquent model.');
        }

        return $users->newQueryWithoutScopes()->whereExists(AuditQuery::scope($filters, $viewer)
            ->where('causer_type', ImmutableActivity::userMorph())
            ->whereColumn('causer_id', $users->getQualifiedKeyName())
            ->toBase())->count();
    }

    /**
     * All rows minus the hidden ones.
     *
     * @param  Figures  $all
     * @param  Figures  $hidden
     * @return Figures
     */
    private static function subtract(array $all, array $hidden): array
    {
        $minus = function (array $from, array $less): array {
            foreach ($less as $key => $total) {
                $from[$key] = ($from[$key] ?? 0) - $total;
            }

            return $from;
        };

        return [
            'events' => $minus($all['events'], $hidden['events']),
            'days' => $minus($all['days'], $hidden['days']),
            'rows_downloaded' => $all['rows_downloaded'] - $hidden['rows_downloaded'],
        ];
    }

    /**
     * Every day of the range (or every week, starting on Monday), empty ones included.
     *
     * @param  array<string, int>  $byDay
     * @return list<AuditCountData>
     */
    private static function buckets(array $byDay, CarbonImmutable $from, CarbonImmutable $to, bool $weekly): array
    {
        $buckets = [];

        for ($day = $from->startOfDay(); $day <= $to; $day = $day->addDay()) {
            $key = ($weekly ? $day->startOfWeek() : $day)->toDateString();
            $buckets[$key] = ($buckets[$key] ?? 0) + ($byDay[$day->toDateString()] ?? 0);
        }

        return array_map(
            fn (string $key, int $value): AuditCountData => new AuditCountData($key, $key, $value),
            array_keys($buckets),
            $buckets,
        );
    }
}
