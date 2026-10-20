<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Exports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Inertia;
use LogicException;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Data\FlashData;
use Yuan\AdminCore\Registry\ModuleRegistry;

/**
 * The only way to download a list (spec §7, AGENTS §5). The current list's
 * search, filters and sort are exported for the requesting user. Up to
 * `admin-core.exports.threshold` rows the file streams now (an Inertia
 * request is sent to the same URL as a plain download); above it an
 * ExportJob writes it and the user finds it under Unduhan. Every download
 * writes an `exported` audit row (user, module, filters, row count).
 */
final class ExportManager
{
    public const array FORMATS = ['xlsx' => ExcelWriter::XLSX, 'csv' => ExcelWriter::CSV];

    /**
     * @param  class-string<ModuleExport<*>>  $export
     */
    public static function for(string $export, Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof Model) {
            throw new LogicException('A download needs a signed-in user.');
        }

        $slug = app(ModuleRegistry::class)->forClass($export)->slug ?? throw new LogicException("{$export} is not in a module.");
        $format = array_key_exists($request->string('format')->toString(), self::FORMATS) ? $request->string('format')->toString() : 'xlsx';
        $params = array_intersect_key($request->query(), array_flip(['search', 'filter', 'sort']));
        $rows = (new $export($user, $params))->query()->count();

        if ($rows <= config()->integer('admin-core.exports.threshold')) {
            // The DataTable asks through Inertia; the browser then fetches the
            // file itself, so the page stays where it is.
            if ($request->header('X-Inertia')) {
                return Inertia::location($request->fullUrl());
            }

            Audit::record(AuditEvent::Exported, meta: ['rows' => $rows, 'filters' => $params, 'format' => $format], module: $slug);

            return Excel::download(new $export($user, $params), self::fileName($slug, $format), self::FORMATS[$format]);
        }

        $queued = QueuedExport::query()->create([
            'user_id' => $user->getAuthIdentifier(),
            'module' => $slug,
            'filters' => [...$params, 'format' => $format],
            'row_count' => $rows,
            'status' => ExportStatus::Processing,
        ]);
        ExportJob::dispatch($queued, $user, $export);

        Inertia::flash((new FlashData(info: __('Ekspor diproses, cek Unduhan'), description: __(':count baris sesuai filter', ['count' => number_format($rows, 0, ',', '.')])))->toArray());

        return back();
    }

    public static function fileName(string $slug, string $format): string
    {
        return $slug.'-'.now()->format('Ymd-His').'.'.$format;
    }
}
