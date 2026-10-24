<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Attachments;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Registry\ModuleRegistry;

/**
 * Files that travel with a record's form (`files.{slot}`): the rules for the
 * input Data, and saving them with the record. A single slot's new file
 * replaces the old one; every upload and replacement is audited on the
 * record, in one audit batch with the record's own change.
 */
final class AttachmentUploads
{
    /**
     * @param  class-string<Model>  $model
     * @return array<string, list<string>>
     */
    public static function rules(string $model, ?Model $record = null): array
    {
        $rules = [];

        foreach (self::slots(new $model) as $key => $slot) {
            $filled = $record !== null && method_exists($record, 'attachments') && $record->attachments($key)->exists();
            $rules = [...$rules, ...$slot->rules($key, $filled)];
        }

        return ['files' => ['array'], ...$rules];
    }

    /**
     * @param  class-string<Model>  $model
     * @return array<string, string>
     */
    public static function attributes(string $model): array
    {
        $names = [];

        foreach (self::slots(new $model) as $key => $slot) {
            $names["files.{$key}"] = mb_strtolower($slot->label);
            $names["files.{$key}.*"] = mb_strtolower($slot->label);
        }

        return $names;
    }

    /**
     * Saves the record (`$save` creates or updates it and returns it) and then
     * its files, in one transaction and one audit batch.
     *
     * @template TModel of Model
     *
     * @param  Closure(): TModel  $save
     * @param  array<string, mixed>  $files
     * @return TModel
     */
    public static function saveWith(Closure $save, array $files, ?Authenticatable $user): Model
    {
        return Audit::batch(fn (): Model => DB::transaction(function () use ($save, $files, $user): Model {
            $record = $save();
            self::save($record, $files, $user);

            return $record;
        }));
    }

    /**
     * Stores the validated files of each slot on the private disk under random
     * names. A stored file is removed again if the transaction fails; a
     * replaced file is removed once it commits.
     *
     * @param  array<string, mixed>  $files  Slot key => an upload, or a list of uploads.
     */
    public static function save(Model $record, array $files, ?Authenticatable $user): void
    {
        if (! method_exists($record, 'attachments')) {
            throw new InvalidArgumentException($record::class.' does not use HasAttachments.');
        }

        $slots = self::slots($record);
        $disk = config()->string('admin-core.attachments.disk');
        $folder = 'attachments/'.self::slugOf($record).'/'.now()->format('Y/m');
        $stored = [];

        try {
            foreach ($slots as $key => $slot) {
                $uploads = array_values(array_filter(is_array($files[$key] ?? null) ? $files[$key] : [$files[$key] ?? null], fn (mixed $file): bool => $file instanceof UploadedFile));

                if ($uploads === []) {
                    continue;
                }

                if (! $slot->multiple) {
                    $uploads = [$uploads[0]];
                    $record->attachments($key)->get()->each(function (Attachment $old) use ($record): void {
                        Audit::record(AuditEvent::AttachmentDeleted, $record, ['name' => $old->original_name, 'slot' => $old->collection]);
                        $old->delete();
                        DB::afterCommit(fn () => Storage::disk($old->disk)->delete($old->path));
                    });
                }

                foreach ($uploads as $upload) {
                    $path = $upload->storeAs($folder, Str::uuid().'.'.$upload->extension(), $disk);

                    if ($path === false) {
                        throw new InvalidArgumentException('The attachment could not be stored.');
                    }

                    $stored[] = $path;
                    $file = new Attachment([
                        'collection' => $key,
                        'disk' => $disk,
                        'path' => $path,
                        'original_name' => self::cleanName($upload->getClientOriginalName()),
                        'mime' => $upload->getMimeType() ?? 'application/octet-stream',
                        'size' => $upload->getSize(),
                        'uploaded_by' => $user?->getAuthIdentifier(),
                    ]);
                    $file->attachable()->associate($record)->save();
                    Audit::record(AuditEvent::AttachmentUploaded, $record, ['name' => $file->original_name, 'size' => $file->size, 'slot' => $key]);
                }
            }
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($stored);

            throw $exception;
        }

        // ponytail: a rollback after save() returns leaves these files (random
        // names, no row links them); a sweep of row-less files is the upgrade.
    }

    /**
     * @return array<string, AttachmentSlot>
     */
    public static function slots(Model $record): array
    {
        return method_exists($record, 'attachmentSlots') ? $record->attachmentSlots() : [];
    }

    private static function slugOf(Model $record): string
    {
        return app(ModuleRegistry::class)->forClass($record::class)->slug ?? throw new InvalidArgumentException($record::class.' is not in a module.');
    }

    /** The client's file name as display metadata: no path, no control characters, at most 200 characters. */
    private static function cleanName(string $name): string
    {
        $clean = trim((string) preg_replace('/[\x00-\x1F\x7F]/u', '', basename(str_replace('\\', '/', $name))));

        return Str::limit($clean === '' ? 'berkas' : $clean, 200, '');
    }
}
