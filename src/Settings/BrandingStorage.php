<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Settings;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The one public-disk exception (AGENTS §5): the favicon and the two logos.
 * PNG, JPG, WebP or ICO by content (never SVG), size-limited, a random file
 * name per upload (cache busting); the replaced file is deleted.
 */
final class BrandingStorage
{
    public const string DISK = 'public';

    public const string FOLDER = 'branding';

    /** Setting key => mimes, max KB, shape (square / landscape), minimum height in px. */
    public const array FIELDS = [
        'umum.favicon' => ['mimes' => ['png', 'ico'], 'max_kb' => 256, 'shape' => 'square', 'min' => 16],
        'umum.logo_persegi' => ['mimes' => ['png', 'jpg', 'jpeg', 'webp'], 'max_kb' => 1024, 'shape' => 'square', 'min' => 128],
        'umum.logo_lanskap' => ['mimes' => ['png', 'jpg', 'jpeg', 'webp'], 'max_kb' => 1024, 'shape' => 'landscape', 'min' => 32],
    ];

    /**
     * @return list<mixed>
     */
    public static function rules(string $key): array
    {
        $field = self::FIELDS[$key];

        return ['file', 'mimes:'.implode(',', $field['mimes']), 'max:'.$field['max_kb'], function (string $attribute, mixed $value, Closure $fail) use ($field): void {
            $size = $value instanceof UploadedFile ? @getimagesize($value->getRealPath()) : false;

            if ($size === false) {
                $fail(__('Berkas bukan gambar yang bisa dibaca.'));

                return;
            }

            [$width, $height] = $size;

            if ($field['shape'] === 'square' && $width !== $height) {
                $fail(__('Gambar harus persegi (lebar sama dengan tinggi).'));
            } elseif ($field['shape'] === 'landscape' && $width < 1.5 * $height) {
                $fail(__('Lebar logo lanskap minimal 1,5 kali tingginya.'));
            }

            if ($height < $field['min']) {
                $fail(__('Tinggi gambar minimal :min px.', ['min' => $field['min']]));
            }
        }];
    }

    /** Stores the upload under a random name; returns its path on the public disk. */
    public static function store(UploadedFile $file): string
    {
        $extension = $file->guessExtension() ?? 'png';

        return Storage::disk(self::DISK)->putFileAs(self::FOLDER, $file, Str::random(32).'.'.$extension)
            ?: throw new RuntimeException('The branding file could not be stored.');
    }

    public static function delete(?string $path): void
    {
        if ($path !== null && str_starts_with($path, self::FOLDER.'/')) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
