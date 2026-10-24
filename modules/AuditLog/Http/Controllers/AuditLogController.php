<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\AuditLog\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Yuan\AdminCore\Audit\ActivityPresenter;
use Yuan\AdminCore\Audit\AuditCategory;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Data\Audit\ActivityDetailData;
use Yuan\AdminCore\Data\Audit\ActivityItemData;
use Yuan\AdminCore\Data\DataTableFilterData;
use Yuan\AdminCore\Data\DataTableStateData;
use Yuan\AdminCore\Data\OptionData;
use Yuan\AdminCore\Data\PaginatedData;
use Yuan\AdminCore\DataTable\DataTableQuery;
use Yuan\AdminCore\Exports\ExportManager;
use Yuan\AdminCore\Modules\AuditLog\Data\AuditFilterData;
use Yuan\AdminCore\Modules\AuditLog\Data\AuditFilterOptionsData;
use Yuan\AdminCore\Modules\AuditLog\Data\AuditSummaryData;
use Yuan\AdminCore\Modules\AuditLog\Exports\AuditLogExport;
use Yuan\AdminCore\Modules\AuditLog\Services\AuditFilterOptions;
use Yuan\AdminCore\Modules\AuditLog\Services\AuditQuery;
use Yuan\AdminCore\Modules\AuditLog\Services\AuditSummary;

/**
 * Log Aktivitas (frontend-behavior §8.3): the list renders first, the summary
 * arrives deferred, and `?event_id=` opens the drawer. Read-only.
 */
final class AuditLogController
{
    public function __construct(private readonly ActivityPresenter $presenter) {}

    public function index(Request $request, AuditFilterData $filters, AuditSummary $summary, AuditFilterOptions $options): Response
    {
        Gate::authorize('viewAny', ImmutableActivity::class);
        $viewer = self::viewer($request);
        [$from, $to] = $filters->period();

        return Inertia::render('AuditLog::Index', [
            'events' => PaginatedData::fromPaginator(
                AuditQuery::events($filters, $viewer)->paginate(DataTableQuery::perPage($request)),
                ActivityItemData::class,
                fn (Collection $rows): array => $this->presenter->items($rows, $viewer),
            ),
            'table' => new DataTableStateData(
                search: $filters->search,
                sort: null,
                filter: array_filter($filters->filter, fn (string $value): bool => $value !== ''),
                per_page: DataTableQuery::perPage($request),
                per_page_options: DataTableQuery::PER_PAGE_OPTIONS,
                sortable: [],
                filters: self::listFilters($filters),
                searchable: true,
                params: $filters->rangeParams(),
            ),
            'filters' => new AuditFilterOptionsData(
                range: $filters->range,
                from: $from->toDateString(),
                to: $to->toDateString(),
                user: $filters->user() !== null ? (string) $filters->user() : null,
                module: $filters->module(),
                users: $options->users($viewer),
                modules: $options->modules($viewer),
                max_days: AuditFilterData::MAX_DAYS,
            ),
            'summary' => Inertia::defer(fn (): AuditSummaryData => $summary->for($filters, $viewer)),
            'event' => fn (): ?ActivityDetailData => $this->detail($filters, $viewer),
        ]);
    }

    public function download(Request $request): SymfonyResponse
    {
        Gate::authorize('download', ImmutableActivity::class);

        return ExportManager::for(AuditLogExport::class, $request);
    }

    private function detail(AuditFilterData $filters, Authenticatable $viewer): ?ActivityDetailData
    {
        $row = $filters->event_id !== null ? ImmutableActivity::query()->visibleTo($viewer)->find($filters->event_id) : null;

        return $row instanceof ImmutableActivity ? $this->presenter->detail($row, $viewer) : null;
    }

    /**
     * The list toolbar's own filters: the event group, and the batch while one is open.
     *
     * @return list<DataTableFilterData>
     */
    private static function listFilters(AuditFilterData $filters): array
    {
        $list = [new DataTableFilterData(
            key: 'event',
            label: __('Event'),
            options: array_map(fn (AuditCategory $category): OptionData => new OptionData($category->value, $category->label()), AuditCategory::cases()),
        )];

        if (($batch = $filters->batch()) !== null) {
            $list[] = new DataTableFilterData(key: 'batch', label: __('Batch'), options: [new OptionData($batch, __('Satu permintaan'))]);
        }

        return $list;
    }

    private static function viewer(Request $request): Authenticatable
    {
        $user = $request->user();
        abort_unless($user instanceof Authenticatable, 403);

        return $user;
    }
}
