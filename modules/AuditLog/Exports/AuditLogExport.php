<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\AuditLog\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Yuan\AdminCore\Audit\ActivityPresenter;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Exports\ListExport;
use Yuan\AdminCore\Modules\AuditLog\Data\AuditFilterData;
use Yuan\AdminCore\Modules\AuditLog\Services\AuditQuery;

/**
 * The Log Aktivitas download: the list with its current range, filters and
 * search, under the same SA visibility rule. No changed values: they stay in
 * the app, where masked fields are marked.
 *
 * @extends ListExport<ImmutableActivity>
 */
final class AuditLogExport extends ListExport
{
    public static function params(): array
    {
        return ['range', 'from', 'to', 'filter', 'search'];
    }

    public function headings(): array
    {
        return [__('Waktu'), __('User'), __('Level'), __('Modul'), __('Event'), __('Subjek'), __('Tipe'), __('Keterangan')];
    }

    public function query(): Builder
    {
        // Deleted accounts keep their name in the file.
        return AuditQuery::events(AuditFilterData::validateAndCreate($this->params), $this->user)
            ->with(['causer' => fn (MorphTo $causer): MorphTo => $causer->withoutGlobalScope(SoftDeletingScope::class)->with('roles')]);
    }

    protected function row(Model $row): array
    {
        $item = app(ActivityPresenter::class)->single($row);

        return [
            $row->created_at?->format('Y-m-d H:i:s'),
            $item->causer_name ?? $item->attempted_username,
            $item->causer_level,
            $item->module_label,
            $item->event_label,
            $item->subject_label,
            $item->subject_type,
            $item->summary,
        ];
    }
}
