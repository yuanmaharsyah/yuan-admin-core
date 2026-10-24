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
use Inertia\Inertia;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Data\AttachmentData;
use Yuan\AdminCore\Data\AttachmentsData;
use Yuan\AdminCore\Data\AttachmentSlotData;
use Yuan\AdminCore\Data\FlashData;
use Yuan\AdminCore\Data\SignedUrlData;
use Yuan\AdminCore\DataTable\HasVisibilityScope;
use Yuan\AdminCore\Registry\ModuleRegistry;

/**
 * Files of a module's records (spec §7), in named slots. A module adds the
 * routes with `AttachmentController::routes(Model::class)`; the record is
 * always loaded through `visibleTo()`, downloading needs its `view` policy,
 * deleting its `update` policy. Downloads are 5-minute signed URLs from a
 * private disk. Uploads go with the record's form (AttachmentUploads).
 */
final class AttachmentController
{
    /**
     * `{slug}.attachments.url|download` (+ `destroy` when the module declares
     * `edit`), inside the module's route group. Uploads go with the record's
     * form (AttachmentUploads).
     *
     * @param  class-string<Model>  $model  Uses HasAttachments and HasVisibilityScope.
     */
    public static function routes(string $model): void
    {
        foreach ([HasAttachments::class, HasVisibilityScope::class] as $trait) {
            if (! in_array($trait, class_uses_recursive($model), true)) {
                throw new InvalidArgumentException("Attachments of {$model} need ".class_basename($trait).'.');
            }
        }

        $module = app(ModuleRegistry::class)->forClass($model) ?? throw new InvalidArgumentException("{$model} is not in a module.");
        $view = "permission:{$module->slug}.view";

        Route::get('attachments/{attachment}/url', [self::class, 'url'])->whereNumber('attachment')->middleware($view)->defaults('model', $model)->name('attachments.url');
        Route::get('attachments/{attachment}/download', [self::class, 'download'])->whereNumber('attachment')->middleware([$view, 'signed'])->defaults('model', $model)->name('attachments.download');

        if (in_array('edit', $module->actions, true)) {
            Route::delete('attachments/{attachment}', [self::class, 'destroy'])->whereNumber('attachment')->middleware("permission:{$module->slug}.edit")->defaults('model', $model)->name('attachments.destroy');
        }
    }

    /**
     * The `attachments` prop of a Show, Edit or Create page: every slot with
     * its files (none for a record not saved yet).
     */
    public static function data(Model $record, ?Authenticatable $user): AttachmentsData
    {
        $slug = self::slugOf($record::class);
        $mayDelete = $record->exists && $user !== null && Route::has("{$slug}.attachments.destroy") && Gate::forUser($user)->allows('update', $record);
        $files = $record->exists && method_exists($record, 'attachments') ? $record->attachments()->with('uploader')->get()->groupBy('collection') : collect();
        $slots = [];

        foreach (AttachmentUploads::slots($record) as $key => $slot) {
            $slots[] = new AttachmentSlotData(
                key: $key,
                label: $slot->label,
                multiple: $slot->multiple,
                required: $slot->required,
                accept: $slot->mimes,
                max_kb: $slot->maxKb,
                items: array_values(($files[$key] ?? collect())->map(fn (Attachment $file): AttachmentData => self::item($file, $slug, $mayDelete))->all()),
            );
        }

        return new AttachmentsData(slots: $slots);
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
        Audit::record(AuditEvent::AttachmentDownloaded, $file->attachable, ['name' => $file->original_name, 'slot' => $file->collection]);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    /**
     * @param  class-string<Model>  $model
     */
    public function destroy(Request $request, int $attachment, string $model): RedirectResponse
    {
        $file = self::attachment($model, $attachment, $request->user(), 'update');

        DB::transaction(function () use ($file): void {
            Audit::record(AuditEvent::AttachmentDeleted, $file->attachable, ['name' => $file->original_name, 'slot' => $file->collection]);
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
}
