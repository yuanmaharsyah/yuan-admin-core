<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Exports;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yuan\AdminCore\Data\BreadcrumbData;
use Yuan\AdminCore\Data\BreadcrumbsData;
use Yuan\AdminCore\Data\DownloadItemData;
use Yuan\AdminCore\Data\DownloadListData;
use Yuan\AdminCore\Data\SignedUrlData;
use Yuan\AdminCore\Registry\ModuleRegistry;

/**
 * The signed-in user's queued downloads (frontend-behavior §8.4): a list, a
 * fresh signed URL on click, and the file. Every lookup is limited to the
 * user's own exports, so another user's export is a 404, never a 403. The
 * file was audited as `exported` when it was written; fetching it adds no row.
 */
final class DownloadsController
{
    /**
     * `account.downloads.index|url|file`, inside the project's signed-in route group.
     */
    public static function routes(): void
    {
        Route::get('account/downloads', [self::class, 'index'])->name('account.downloads.index');
        Route::get('account/downloads/{export}/url', [self::class, 'url'])->whereNumber('export')->name('account.downloads.url');
        Route::get('account/downloads/{export}/file', [self::class, 'file'])->whereNumber('export')->middleware('signed')->name('account.downloads.file');
    }

    public function index(Request $request): Response
    {
        $registry = app(ModuleRegistry::class);
        $exports = QueuedExport::query()->where('user_id', $request->user()?->getAuthIdentifier())->latest('id')->limit(100)->get();

        return Inertia::render('Core::Downloads/Index', [
            'downloads_list' => new DownloadListData(array_values($exports->map(fn (QueuedExport $export): DownloadItemData => new DownloadItemData(
                id: $export->id,
                module_label: __($registry->find($export->module)->label ?? $export->module),
                file_name: $export->path !== null ? basename($export->path) : null,
                row_count: $export->row_count,
                status: $export->displayStatus(),
                error: $export->displayStatus() === ExportStatus::Failed ? $export->error : null,
                created_at: $export->created_at?->toIso8601String() ?? '',
                expires_at: $export->expires_at?->toIso8601String(),
                url_href: $export->isDownloadable() ? route('account.downloads.url', $export) : null,
            ))->all()), config()->integer('admin-core.exports.ttl_hours')),
            'breadcrumbs' => BreadcrumbsData::of(new BreadcrumbData(label: __('Unduhan'), href: null)),
        ]);
    }

    public function url(Request $request, int $export): SignedUrlData
    {
        $download = self::downloadable($request, $export);

        return new SignedUrlData(url: URL::temporarySignedRoute(
            'account.downloads.file',
            now()->addMinutes(config()->integer('admin-core.attachments.url_ttl_minutes')),
            ['export' => $download->id],
        ));
    }

    public function file(Request $request, int $export): StreamedResponse
    {
        $download = self::downloadable($request, $export);

        return Storage::disk(config()->string('admin-core.exports.disk'))->download((string) $download->path, basename((string) $download->path));
    }

    /**
     * The user's own export (404 for anyone else's), ready and unexpired with
     * its file present (410 otherwise).
     */
    private static function downloadable(Request $request, int $id): QueuedExport
    {
        $download = QueuedExport::query()->where('user_id', $request->user()?->getAuthIdentifier())->findOrFail($id);
        $disk = Storage::disk(config()->string('admin-core.exports.disk'));

        abort_unless($download->isDownloadable() && $disk->exists((string) $download->path), 410, __('Unduhan ini sudah tidak tersedia.'));

        return $download;
    }
}
