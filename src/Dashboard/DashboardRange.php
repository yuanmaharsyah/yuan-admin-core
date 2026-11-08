<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Dashboard;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/** The Beranda range (`?range=` 7d / 30d / 90d, default 30d): whole days up to today. */
final readonly class DashboardRange
{
    public const array DAYS = ['7d' => 7, '30d' => 30, '90d' => 90];

    public CarbonImmutable $from;

    public CarbonImmutable $to;

    /** `$from` moves the window back (previous()); by default it ends today. */
    public function __construct(public string $key = '30d', ?CarbonImmutable $from = null)
    {
        $days = self::DAYS[$key] ?? throw new InvalidArgumentException("Unknown range [{$key}].");
        $this->from = $from ?? CarbonImmutable::today()->subDays($days - 1);
        $this->to = $this->from->addDays($days - 1)->endOfDay();
    }

    /** The range of the same length right before this one (for `change`). */
    public function previous(): self
    {
        return new self($this->key, $this->from->subDays(self::DAYS[$this->key]));
    }

    /** Percent change from `$previous` to `$current`, one decimal; null when there is nothing to compare with. */
    public static function change(int|float $current, int|float $previous): ?float
    {
        return (float) $previous === 0.0 ? null : round(($current - $previous) / abs($previous) * 100, 1);
    }

    public static function from(mixed $key): self
    {
        return new self(is_string($key) && isset(self::DAYS[$key]) ? $key : '30d');
    }
}
