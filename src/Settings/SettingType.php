<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Settings;

use Closure;
use Illuminate\Support\Facades\Storage;

/**
 * The value types of the settings registry (spec §7.3): how a value is
 * validated, stored, shown in the form and exposed to the pages.
 */
enum SettingType: string
{
    case String = 'string';
    case Text = 'text';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case Enum = 'enum';
    case Phone = 'phone';
    case Whatsapp = 'whatsapp';
    case PostalCode = 'postal_code';
    case Social = 'social';
    case Image = 'image';

    /** Platform => [domain, URL for a bare handle]. */
    public const array SOCIAL = [
        'instagram' => ['instagram.com', 'https://instagram.com/%s'],
        'x' => ['x.com', 'https://x.com/%s'],
        'tiktok' => ['tiktok.com', 'https://www.tiktok.com/@%s'],
        'facebook' => ['facebook.com', 'https://facebook.com/%s'],
        'youtube' => ['youtube.com', 'https://youtube.com/@%s'],
        'linkedin' => ['linkedin.com', 'https://linkedin.com/company/%s'],
    ];

    /**
     * @return list<mixed>
     */
    public function rules(SettingDefinition $definition): array
    {
        $parsed = fn (string $message): Closure => function (string $attribute, mixed $value, Closure $fail) use ($definition, $message): void {
            if (is_string($value) && trim($value) !== '' && $this->normalize($value, $definition) === null) {
                $fail(__($message));
            }
        };

        return [...match ($this) {
            self::String => ['nullable', 'string', 'max:255'],
            self::Text => ['nullable', 'string', 'max:2000'],
            self::Integer => ['nullable', 'integer'],
            self::Boolean => ['required', 'boolean'],
            self::Enum => ['required', 'string', 'in:'.implode(',', array_keys($definition->options))],
            self::Phone => ['nullable', 'string', 'max:30', $parsed('Nomor telepon tidak valid. Contoh: 0812 3456 7890, 021 1234 5678 atau +62 812 3456 7890.')],
            self::Whatsapp => ['nullable', 'string', 'max:30', $parsed('Nomor WhatsApp harus nomor seluler. Contoh: 0812 3456 7890.')],
            self::PostalCode => ['nullable', 'string', $parsed('Kode pos harus 5 angka.')],
            self::Social => ['nullable', 'string', 'max:255', $parsed('Isi alamat '.$definition->platform().' atau nama akunnya saja.')],
            self::Image => ['nullable', ...BrandingStorage::rules($definition->key)],
        }, ...$definition->rules];
    }

    /** The stored form of a valid input; null for an empty or (to the rules) invalid one. */
    public function normalize(mixed $value, SettingDefinition $definition): mixed
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return $this === self::Boolean ? false : null;
        }

        $text = is_scalar($value) ? trim((string) $value) : '';

        return match ($this) {
            self::String, self::Text, self::Enum, self::Image => $text,
            self::Integer => (int) $text,
            self::Boolean => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            self::Phone => ($rest = self::nationalNumber($text)) !== null && preg_match('/^[2-9]\d{6,11}$/', $rest) === 1 ? '+62'.$rest : null,
            self::Whatsapp => ($rest = self::nationalNumber($text)) !== null && preg_match('/^8\d{7,11}$/', $rest) === 1 ? '62'.$rest : null,
            self::PostalCode => preg_match('/^\d{5}$/', $text) === 1 ? $text : null,
            self::Social => self::socialUrl($definition->platform(), $text),
        };
    }

    /** The stored value as the form shows it. */
    public function display(mixed $value): mixed
    {
        return match ($this) {
            self::Phone => is_string($value) ? self::formatNumber(substr($value, 3)) : null,
            self::Whatsapp => is_string($value) ? self::formatNumber(substr($value, 2)) : null,
            self::Image => is_string($value) ? Storage::disk(BrandingStorage::DISK)->url($value) : null,
            default => $value,
        };
    }

    /** The stored value as pages and project code get it (`settings()` keeps the stored form). */
    public function publicValue(mixed $value): mixed
    {
        return match ($this) {
            self::Whatsapp => is_string($value) ? 'https://wa.me/'.$value : null,
            self::Image => $this->display($value),
            default => $value,
        };
    }

    /** The number after the country code: `08…`, `628…` and `+628…` all give `8…`. */
    private static function nationalNumber(string $text): ?string
    {
        $digits = preg_replace('/[\s\-().]/', '', $text) ?? '';

        return match (true) {
            str_starts_with($digits, '+62') => substr($digits, 3),
            str_starts_with($digits, '62') => substr($digits, 2),
            str_starts_with($digits, '0') => substr($digits, 1),
            default => null,
        };
    }

    /** +62 812-3456-7890 for a mobile number; a landline keeps its digits after +62. */
    private static function formatNumber(string $rest): string
    {
        return str_starts_with($rest, '8')
            ? '+62 '.substr($rest, 0, 3).'-'.substr($rest, 3, 4).(strlen($rest) > 7 ? '-'.substr($rest, 7) : '')
            : '+62 '.$rest;
    }

    /** A URL on the platform's own domain (www. and m. dropped, twitter.com is x.com), or a handle made into one. */
    private static function socialUrl(string $platform, string $text): ?string
    {
        [$domain, $profile] = self::SOCIAL[$platform] ?? [null, null];

        if ($domain === null || $profile === null) {
            return null;
        }

        if (str_contains($text, '/')) {
            if (preg_match('~^(?:https?://)?([^/\s?#]+)(/[^\s?#]*)~i', $text, $parts) !== 1) {
                return null;
            }

            $host = (string) preg_replace('/^(www\.|m\.)/', '', strtolower($parts[1]));
            $host = $platform === 'x' && $host === 'twitter.com' ? 'x.com' : $host;
            $path = rtrim($parts[2], '/');

            return $host === $domain && $path !== '' ? "https://{$domain}{$path}" : null;
        }

        $handle = ltrim($text, '@');

        return preg_match('/^[A-Za-z0-9._-]{1,100}$/', $handle) === 1 ? sprintf($profile, $handle) : null;
    }
}
