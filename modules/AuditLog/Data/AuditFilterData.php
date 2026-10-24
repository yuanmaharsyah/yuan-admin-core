<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\AuditLog\Data;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Audit\AuditCategory;

/**
 * The Log Aktivitas URL (frontend-behavior §8.3): `range` (7d, 30d, 90d or
 * custom with `from` / `to`, at most 366 days), `filter[user|module|event|batch]`,
 * `search`, and `event_id` for the open drawer. Range, user and module drive
 * the summary too; event, batch and search only the list.
 */
#[TypeScript]
final class AuditFilterData extends Data
{
    public const array RANGES = ['7d' => 7, '30d' => 30, '90d' => 90];

    public const int MAX_DAYS = 366;

    /**
     * @param  array<string, string>  $filter
     */
    public function __construct(
        public string $range = '30d',
        public ?string $from = null,
        public ?string $to = null,
        public array $filter = [],
        public ?string $search = null,
        public ?int $event_id = null,
    ) {}

    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        $from = $context->payload['from'] ?? null;

        return [
            'range' => ['sometimes', Rule::in([...array_keys(self::RANGES), 'custom'])],
            'from' => ['nullable', 'required_if:range,custom', 'date_format:Y-m-d'],
            'to' => [
                'nullable',
                'required_if:range,custom',
                'date_format:Y-m-d',
                'after_or_equal:from',
                function (string $attribute, mixed $value, Closure $fail) use ($from): void {
                    if (is_string($from) && is_string($value) && strtotime($value) !== false && strtotime($from) !== false
                        && CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($value)) + 1 > self::MAX_DAYS) {
                        $fail(__('Rentang tanggal paling lama :days hari.', ['days' => self::MAX_DAYS]));
                    }
                },
            ],
            'filter' => ['sometimes', 'array'],
            'filter.user' => ['nullable', 'integer'],
            'filter.module' => ['nullable', 'string', 'max:60'],
            'filter.event' => ['nullable', Rule::enum(AuditCategory::class)],
            'filter.batch' => ['nullable', 'uuid'],
            'search' => ['nullable', 'string', 'max:100'],
            'event_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return ['from' => __('tanggal awal'), 'to' => __('tanggal akhir'), 'range' => __('rentang')];
    }

    /**
     * The first and last moment of the range, in the app timezone.
     *
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    public function period(): array
    {
        if ($this->range === 'custom' && $this->from !== null && $this->to !== null) {
            return [CarbonImmutable::parse($this->from)->startOfDay(), CarbonImmutable::parse($this->to)->endOfDay()];
        }

        $days = self::RANGES[$this->range] ?? 30;

        return [CarbonImmutable::today()->subDays($days - 1), CarbonImmutable::today()->endOfDay()];
    }

    public function user(): ?int
    {
        $user = $this->filter['user'] ?? null;

        return is_numeric($user) ? (int) $user : null;
    }

    public function module(): ?string
    {
        return ($this->filter['module'] ?? '') !== '' ? $this->filter['module'] : null;
    }

    public function category(): ?AuditCategory
    {
        return AuditCategory::tryFrom($this->filter['event'] ?? '');
    }

    public function batch(): ?string
    {
        return ($this->filter['batch'] ?? '') !== '' ? $this->filter['batch'] : null;
    }

    /**
     * The page's own URL parameters, which every list visit and download keeps.
     *
     * @return array<string, string>
     */
    public function rangeParams(): array
    {
        return $this->range === 'custom'
            ? ['range' => 'custom', 'from' => (string) $this->from, 'to' => (string) $this->to]
            : ($this->range === '30d' ? [] : ['range' => $this->range]);
    }
}
