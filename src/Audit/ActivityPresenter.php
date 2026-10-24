<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Yuan\AdminCore\Data\Audit\ActivityDetailData;
use Yuan\AdminCore\Data\Audit\ActivityItemData;
use Yuan\AdminCore\Data\Audit\DiffRowData;
use Yuan\AdminCore\Data\Audit\LabelValueData;
use Yuan\AdminCore\DataTable\HasVisibilityScope;
use Yuan\AdminCore\Registry\ModuleRegistry;

/**
 * Audit rows as the Log Aktivitas and the Riwayat tabs show them: who (name
 * and level, deleted accounts included), which module, which event, about
 * what, and the changed fields. Everything it reads was already masked when
 * the row was written.
 */
final readonly class ActivityPresenter
{
    public function __construct(private ModuleRegistry $registry, private Masker $masker) {}

    /**
     * @param  Collection<array-key, mixed>  $rows  ImmutableActivity rows, already filtered by `visibleTo($viewer)`.
     * @return list<ActivityItemData>
     */
    public function items(Collection $rows, Authenticatable $viewer, bool $withDiff = false): array
    {
        $causers = $this->causers($rows);
        $batches = $this->batchSizes($rows, $viewer);

        return array_values($rows->whereInstanceOf(ImmutableActivity::class)->map(fn (ImmutableActivity $row): ActivityItemData => $this->item($row, $causers, $batches, $withDiff))->all());
    }

    /**
     * One row whose `causer` (with its roles) is already loaded, without the
     * batch size: for a download, which maps row by row.
     */
    public function single(ImmutableActivity $row): ActivityItemData
    {
        $causer = $row->relationLoaded('causer') ? $row->getRelation('causer') : null;

        return $this->item($row, $causer instanceof Model ? [(int) $causer->getKey() => $causer] : [], [], false);
    }

    public function detail(ImmutableActivity $row, Authenticatable $viewer): ActivityDetailData
    {
        $rows = new EloquentCollection([$row]);
        $item = $this->item($row, $this->causers($rows), $this->batchSizes($rows, $viewer), false);
        $context = $this->properties($row, 'context');
        $meta = $this->properties($row, 'meta');
        unset($meta['subject_label']);

        return new ActivityDetailData(
            item: $item,
            context: array_values(array_filter([
                new LabelValueData(__('User'), $item->causer_name !== null ? trim($item->causer_name.' · '.($item->causer_level ?? ''), ' ·') : ($item->attempted_username !== null ? $item->attempted_username.' · '.__('tidak dikenal') : __('Sistem'))),
                is_string($context['ip'] ?? null) ? new LabelValueData(__('IP'), $context['ip'], mono: true) : null,
                is_string($context['user_agent'] ?? null) ? new LabelValueData(__('Perangkat'), self::device($context['user_agent'])) : null,
                new LabelValueData(__('Sumber'), self::source($context)),
                is_string($context['request_id'] ?? null) ? new LabelValueData(__('ID Permintaan'), $context['request_id'], mono: true) : null,
            ])),
            diff: $this->diff($row),
            notes: array_map(
                fn (string $key, mixed $value): LabelValueData => new LabelValueData(self::metaLabel($key), self::text($value) ?? '—'),
                array_keys($meta),
                $meta,
            ),
            subject_href: $this->subjectHref($row, $viewer),
            batch_href: $item->batch_href,
        );
    }

    /** The Log Aktivitas filtered to one batch, on the day it happened. */
    public static function batchHref(ImmutableActivity $row): ?string
    {
        if ($row->batch_uuid === null || ! Route::has('audit-log.index')) {
            return null;
        }

        $day = $row->created_at?->toDateString();

        return route('audit-log.index', ['range' => 'custom', 'from' => $day, 'to' => $day, 'filter' => ['batch' => $row->batch_uuid]]);
    }

    /**
     * @param  array<int, Model>  $causers
     * @param  array<string, int>  $batches
     */
    private function item(ImmutableActivity $row, array $causers, array $batches, bool $withDiff): ActivityItemData
    {
        $event = (string) $row->event;
        $known = AuditEvent::tryFrom($event);
        $causer = $row->causer_id !== null ? ($causers[(int) $row->causer_id] ?? null) : null;
        $meta = $this->properties($row, 'meta');
        $diff = $this->diff($row);
        $batchSize = $row->batch_uuid !== null ? ($batches[$row->batch_uuid] ?? null) : null;

        return new ActivityItemData(
            id: (int) $row->getKey(),
            created_at: (string) $row->created_at?->toIso8601String(),
            causer_name: $causer?->getAttribute('name'),
            causer_level: $causer !== null && method_exists($causer, 'level') ? $causer->level()?->label : null,
            attempted_username: $causer === null && is_string($meta['username'] ?? null) ? $meta['username'] : null,
            module_label: $this->moduleLabel($row->log_name),
            event: $event,
            event_label: $known?->label() ?? Str::ucfirst(str_replace('_', ' ', $event)),
            tone: $known?->badge() ?? 'neutral',
            subject_label: is_string($meta['subject_label'] ?? null) ? $meta['subject_label'] : null,
            subject_type: $row->subject_type !== null ? ($this->registry->forClass($row->subject_type)->label ?? class_basename($row->subject_type)) : null,
            summary: self::summary($known, $diff, $meta),
            batch_uuid: $row->batch_uuid,
            batch_size: $batchSize,
            batch_href: ($batchSize ?? 0) > 1 ? self::batchHref($row) : null,
            diff: $withDiff ? $diff : [],
        );
    }

    /**
     * @return list<DiffRowData>
     */
    private function diff(ImmutableActivity $row): array
    {
        $changes = $row->attribute_changes?->toArray() ?? [];
        $old = is_array($changes['old'] ?? null) ? $changes['old'] : [];
        $new = is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [];
        $rows = [];

        foreach (array_unique([...array_keys($new), ...array_keys($old)]) as $field) {
            $rows[] = new DiffRowData(
                field: Str::headline((string) $field),
                old: self::text($old[$field] ?? null),
                new: self::text($new[$field] ?? null),
                masked: $this->masker->rule((string) $field) !== null,
            );
        }

        return $rows;
    }

    /**
     * @param  list<DiffRowData>  $diff
     * @param  array<string, mixed>  $meta
     */
    private static function summary(?AuditEvent $event, array $diff, array $meta): ?string
    {
        return match ($event) {
            AuditEvent::Created => self::say('Membuat data'),
            AuditEvent::Updated => trans_choice('Mengubah :count field', count($diff), ['count' => count($diff)]),
            AuditEvent::Deleted => self::say('Memindahkan ke Sampah'),
            AuditEvent::ForceDeleted => self::say('Menghapus permanen'),
            AuditEvent::Restored => self::say('Memulihkan dari Sampah'),
            AuditEvent::Exported => is_numeric($meta['rows'] ?? null) ? self::say(':rows baris', ['rows' => number_format((int) $meta['rows'], 0, ',', '.')]) : null,
            AuditEvent::AttachmentUploaded, AuditEvent::AttachmentDownloaded, AuditEvent::AttachmentDeleted => is_string($meta['name'] ?? null) ? $meta['name'] : null,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function properties(ImmutableActivity $row, string $key): array
    {
        $value = $row->properties?->get($key);

        return is_array($value) ? $value : [];
    }

    private function moduleLabel(?string $logName): string
    {
        return match ($logName) {
            'auth' => __('Autentikasi'),
            'system', null => __('Sistem'),
            default => $this->registry->find($logName)->label ?? $logName,
        };
    }

    private function subjectHref(ImmutableActivity $row, Authenticatable $viewer): ?string
    {
        $module = $row->subject_type !== null ? $this->registry->forClass($row->subject_type) : null;
        $route = $module !== null ? "{$module->slug}.show" : null;

        if ($module === null || $route === null || ! Route::has($route) || $row->subject_id === null
            || ! ($viewer instanceof Authorizable && $viewer->can("{$module->slug}.view"))) {
            return null;
        }

        // Only a record the viewer may open: live and inside their visibleTo() scope.
        if (! is_subclass_of($row->subject_type, Model::class) || ! in_array(HasVisibilityScope::class, class_uses_recursive($row->subject_type), true)) {
            return null;
        }

        return $row->subject_type::query()->whereKey($row->subject_id)->scopes(['visibleTo' => [$viewer]])->exists() ? route($route, $row->subject_id) : null;
    }

    /**
     * @param  Collection<array-key, mixed>  $rows
     * @return array<int, Model>
     */
    private function causers(Collection $rows): array
    {
        $ids = $rows->whereNotNull('causer_id')->pluck('causer_id')->unique()->values()->all();
        $model = config()->string('auth.providers.users.model');

        if ($ids === [] || ! is_subclass_of($model, Model::class)) {
            return [];
        }

        $query = $model::query()->with('roles')->whereKey($ids);

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query->get()->keyBy(fn (Model $user): int => (int) $user->getKey())->all();
    }

    /**
     * How many rows each batch on this page has, as far as the viewer may see.
     *
     * @param  Collection<array-key, mixed>  $rows
     * @return array<string, int>
     */
    private function batchSizes(Collection $rows, Authenticatable $viewer): array
    {
        $uuids = $rows->whereNotNull('batch_uuid')->pluck('batch_uuid')->unique()->values()->all();

        if ($uuids === []) {
            return [];
        }

        /** @var array<string, int> */
        return ImmutableActivity::query()->visibleTo($viewer)->whereIn('batch_uuid', $uuids)
            ->groupBy('batch_uuid')->selectRaw('batch_uuid, count(*) as total')
            ->pluck('total', 'batch_uuid')->map(fn (mixed $total): int => (int) $total)->all();
    }

    /** The stored meta keys of the core events in words; any other key as a headline. */
    private static function metaLabel(string $key): string
    {
        return match ($key) {
            'rows' => self::say('Baris'),
            'format' => self::say('Format'),
            'filters' => self::say('Filter'),
            'queued' => self::say('Diantrekan'),
            'username' => self::say('Username'),
            'reason' => self::say('Alasan'),
            'by_admin' => self::say('Oleh admin'),
            'before' => self::say('Sebelum tanggal'),
            'retention_days' => self::say('Retensi (hari)'),
            'files' => self::say('Berkas dihapus'),
            'added' => self::say('Ditambahkan'),
            'removed' => self::say('Dicabut'),
            'name' => self::say('Nama berkas'),
            'size' => self::say('Ukuran (byte)'),
            'note' => self::say('Catatan'),
            default => Str::headline($key),
        };
    }

    /**
     * @param  array<string, string>  $replace
     */
    private static function say(string $text, array $replace = []): string
    {
        return __($text, $replace);
    }

    private static function text(mixed $value): ?string
    {
        return match (true) {
            $value === null || $value === '' => null,
            is_bool($value) => $value ? self::say('Ya') : self::say('Tidak'),
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function source(array $context): string
    {
        return match ($context['channel'] ?? null) {
            'cli' => trim('CLI · '.(is_string($context['command'] ?? null) ? $context['command'] : ''), ' ·'),
            'queue' => trim(__('Antrean').' · '.(is_string($context['job'] ?? null) ? class_basename($context['job']) : ''), ' ·'),
            default => trim('Web · '.(is_string($context['route'] ?? null) ? $context['route'] : ''), ' ·'),
        };
    }

    /** "Chrome 141 · Windows" from a user agent; the raw string when it isn't a known browser. */
    private static function device(string $agent): string
    {
        $browser = preg_match('#(Edg|OPR|Firefox|Chrome|Version)/(\d+)#', $agent, $match) === 1
            ? ['Edg' => 'Edge', 'OPR' => 'Opera', 'Version' => 'Safari'][$match[1]] ?? $match[1]
            : null;
        $system = match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        return $browser === null ? Str::limit($agent, 80) : trim("{$browser} {$match[2]} · {$system}", ' ·');
    }
}
