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

    public function __construct(public string $key = '30d')
    {
        $days = self::DAYS[$key] ?? throw new InvalidArgumentException("Unknown range [{$key}].");
        $this->from = CarbonImmutable::today()->subDays($days - 1);
        $this->to = CarbonImmutable::today()->endOfDay();
    }

    public static function from(mixed $key): self
    {
        return new self(is_string($key) && isset(self::DAYS[$key]) ? $key : '30d');
    }
}
