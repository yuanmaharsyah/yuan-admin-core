<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use Illuminate\Support\Str;

/**
 * Applies `admin-core.audit.masked_fields` (spec §7.1, UU PDP) to audit data
 * before it is written. Field patterns may use `*` wildcards.
 */
final readonly class Masker
{
    public const string HIDDEN = '••••';

    /**
     * @param  array<string, 'omit'|'partial'|'full'>  $rules
     */
    public function __construct(private array $rules) {}

    /**
     * @template TKey of array-key
     *
     * @param  array<TKey, mixed>  $values
     * @return array<TKey, mixed>
     */
    public function mask(array $values): array
    {
        $masked = [];

        foreach ($values as $key => $value) {
            $rule = is_string($key) ? $this->rule($key) : null;

            if ($rule === 'omit') {
                continue;
            }

            $masked[$key] = match (true) {
                $value === null => null,
                $rule === 'partial' => $this->partial($value),
                $rule === 'full' => self::HIDDEN,
                is_array($value) => $this->mask($value),
                default => $value,
            };
        }

        return $masked;
    }

    /**
     * @return 'omit'|'partial'|'full'|null
     */
    public function rule(string $field): ?string
    {
        foreach ($this->rules as $pattern => $rule) {
            if (Str::is($pattern, $field)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Keeps the first 4 and last 4 characters; anything that short is hidden entirely.
     */
    private function partial(mixed $value): string
    {
        $text = is_scalar($value) ? (string) $value : '';

        if (mb_strlen($text) <= 8) {
            return self::HIDDEN;
        }

        return mb_substr($text, 0, 4).self::HIDDEN.mb_substr($text, -4);
    }
}
