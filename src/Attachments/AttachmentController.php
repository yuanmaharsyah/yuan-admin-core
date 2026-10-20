<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Attachments;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Data\AttachmentData;
use Yuan\AdminCore\Data\AttachmentsData;
use Yuan\AdminCore\Data\FlashData;
use Yuan\AdminCore\Data\SignedUrlData;
use Yuan\AdminCore\Data\UploadAttachmentData;
use Yuan\AdminCore\DataTable\HasVisibilityScope;
use Yuan\AdminCore\Registry\ModuleRegistry;

/**
 * Files of a module's records (spec §7). A module adds the routes with
 * `AttachmentController::routes(Model::class)`; the record is always loaded
 * through `visibleTo()`, viewing and downloading need its `view` policy,
 * uploading and deleting its `update` policy. Downloads are 5-minute signed
 * URLs from a private disk.
 */
final class AttachmentController
{
    /**
     * `{slug}.attachments.store|url|download|destroy`, inside the module's route group.
     *
     * @param  class-string<Model>  $model  Uses HasAttachments and HasVisibilityScope; its module declares `view` and `edit`.
     */
    public static function routes(string $model): void
    {
        foreach ([HasAttachments::class, HasVisibilityScope::class] as $trait) {
            if (! in_array($trait, class_uses_recursive($model), true)) {
                throw new InvalidArgumentException("Attachments of {$model} need ".class_basename($trait).'.');
            }
        }

        $module = app(ModuleRegistry::class)->forClass($model);

        if ($module === null || array_diff(['view', 'edit'], $module->actions) !== []) {
            throw new InvalidArgumentException("Attachments of {$model} need a module that declares view and edit.");
        }

        $view = "permission:{$module->slug}.view";
        $edit = "permission:{$module->slug}.edit";

        Route::post('{record}/attachments', [self::class, 'store'])->whereNumber('record')->middleware($edit)->defaults('model', $model)->name('attachments.store');
        Route::get('attachments/{attachment}/url', [self::class, 'url'])->whereNumber('attachment')->middleware($view)->defaults('model', $model)->name('attachments.url');
        Route::get('attachments/{attachment}/download', [self::class, 'download'])->whereNumber('attachment')->middleware([$view, 'signed'])->defaults('model', $model)->name('attachments.download');
        Route::delete('attachments/{attachment}', [self::class, 'destroy'])->whereNumber('attachment')->middleware($edit)->defaults('model', $model)->name('attachments.destroy');
    }

    /**
     * The `attachments` prop of a Show page.
     */
    public static function data(Model $record, ?Authenticatable $user): AttachmentsData
    {
        $slug = self::slugOf($record::class);
        $mayEdit = $user !== null && Gate::forUser($user)->allows('update', $record);
        $rules = method_exists($record, 'attachmentRules') ? $record->attachmentRules() : ['mimes' => [], 'max_kb' => 0];
        $items = method_exists($record, 'attachments') ? $record->attachments()->with('uploader')->get() : collect();

        return new AttachmentsData(
            items: array_values($items->map(fn (Attachment $file): AttachmentData => self::item($file, $slug, $mayEdit))->all()),
            upload_href: $mayEdit ? route("{$slug}.attachments.store", $record) : null,
            accept: $rules['mimes'],
            max_kb: $rules['max_kb'],
        );
    }

    /**
     * @param  class-string<Model>  $model
     */
    public function store(Request $request, UploadAttachmentData $data, int $record, string $model): AttachmentData
    {
        $parent = self::record($model, $record, $request->user());
        Gate::authorize('update', $parent);

        $disk = config()->string('admin-core.attachments.disk');
        $slug = self::slugOf($model);
        $path = $data->file->storeAs("attachments/{$slug}/".now()->format('Y/m'), Str::uuid().'.'.$data->file->extension(), $disk);
        $name = self::cleanName($data->file->getClientOriginalName());

        $file = DB::transaction(function () use ($parent, $disk, $path, $name, $data, $request): Attachment {
            $file = new Attachment([
                'disk' => $disk,
                'path' => $path,
                'original_name' => $name,
                'mime' => $data->file->getMimeType() ?? 'application/octet-stream',
                'size' => $data->file->getSize(),
                'uploaded_by' => $request->user()?->getAuthIdentifier(),
            ]);
            $file->attachable()->associate($parent)->save();
            Audit::record(AuditEvent::AttachmentUploaded, $parent, ['name' => $name, 'size' => $file->size]);

            return $file;
        });

        return self::item($file->load('uploader'), $slug, true);
    }

    /**
     * @param  class-string<Model>  $model
     */
    public function url(Request $request, int $attachment, string $model): SignedUrlData
    {
        $file = self::attachment($model, $attachment, $request->user());

        return new SignedUrlData(url: URL::temporarySignedRoute(
            self::slugOf($model).'.attachments.download',
            now()->addMinutes(config()->integer('admin-core.attachments.url_ttl_minutes')),
            ['attachment' => $file->id],
        ));
    }

    /**
     * @param  class-string<Model>  $model
     */
    public function download(Request $request, int $attachment, string $model): StreamedResponse
    {
        $file = self::attachment($model, $attachment, $request->user());
        Audit::record(AuditEvent::AttachmentDownloaded, $file->attachable, ['name' => $file->original_name]);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    /**
     * @param  class-string<Model>  $model
     */
    public function destroy(Request $request, int $attachment, string $model): RedirectResponse
    {
        $file = self::attachment($model, $attachment, $request->user(), 'update');

        DB::transaction(function () use ($file): void {
            Audit::record(AuditEvent::AttachmentDeleted, $file->attachable, ['name' => $file->original_name]);
            $file->delete();
        });
        Storage::disk($file->disk)->delete($file->path);
        Inertia::flash((new FlashData(success: __('Lampiran dihapus.'), description: $file->original_name))->toArray());

        return back();
    }

    private static function item(Attachment $file, string $slug, bool $mayEdit): AttachmentData
    {
        $uploader = $file->uploader?->getAttribute('name');

        return new AttachmentData(
            id: $file->id,
            name: $file->original_name,
            mime: $file->mime,
            size: $file->size,
            uploaded_by: is_string($uploader) ? $uploader : null,
            created_at: $file->created_at->toIso8601String(),
            url_href: route("{$slug}.attachments.url", $file),
            destroy_href: $mayEdit ? route("{$slug}.attachments.destroy", $file) : null,
        );
    }

    /**
     * The parent record, through `visibleTo()`; 404 when it is hidden or gone.
     *
     * @param  class-string<Model>  $model
     */
    private static function record(string $model, int $id, ?Authenticatable $user): Model
    {
        $query = $model::query();
        $instance = $query->getModel();

        if (method_exists($instance, 'scopeVisibleTo')) {
            $instance->scopeVisibleTo($query, $user);
        }

        return $query->findOrFail($id);
    }

    /**
     * An attachment of this model whose record the user may see (`view`, or `update` to delete).
     *
     * @param  class-string<Model>  $model
     */
    private static function attachment(string $model, int $id, ?Authenticatable $user, string $ability = 'view'): Attachment
    {
        $file = Attachment::query()->where('attachable_type', (new $model)->getMorphClass())->findOrFail($id);
        $parent = self::record($model, $file->attachable_id, $user);
        Gate::authorize($ability, $parent);
        $file->setRelation('attachable', $parent);

        return $file;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private static function slugOf(string $model): string
    {
        return app(ModuleRegistry::class)->forClass($model)->slug ?? throw new InvalidArgumentException("{$model} is not in a module.");
    }

    /** The client's file name as display metadata: no path, no control characters, at most 200 characters. */
    private static function cleanName(string $name): string
    {
        $clean = trim((string) preg_replace('/[\x00-\x1F\x7F]/u', '', basename(str_replace('\\', '/', $name))));

        return Str::limit($clean === '' ? 'berkas' : $clean, 200, '');
    }
}
