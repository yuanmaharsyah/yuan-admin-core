<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelData\Data;
use Yuan\AdminCore\Dashboard\DashboardRange;
use Yuan\AdminCore\Dashboard\Widget;
use Yuan\AdminCore\Dashboard\WidgetType;
use Yuan\AdminCore\Data\Widgets\ListRowData;
use Yuan\AdminCore\Data\Widgets\ListWidgetData;
use Yuan\AdminCore\Data\Widgets\StatWidgetData;

uses(RefreshDatabase::class);

/** A widget of any type returning the given data. */
function widgetOf(WidgetType $type, Data $data, bool $highlight = false): Widget
{
    return new class($type, $data, $highlight) extends Widget
    {
        public function __construct(private WidgetType $kind, private Data $payload, private bool $isHighlight) {}

        public function key(): string
        {
            return 'invoices.probe';
        }

        public function label(): string
        {
            return 'Probe';
        }

        public function type(): WidgetType
        {
            return $this->kind;
        }

        public function highlight(): bool
        {
            return $this->isHighlight;
        }

        public function data(Authenticatable $user, DashboardRange $range): Data
        {
            return $this->payload;
        }
    };
}

it('gives every type a Data class', function (WidgetType $type): void {
    expect(is_subclass_of($type->dataClass(), Data::class))->toBeTrue();
})->with(WidgetType::cases());

it('compares a range with the same number of days right before it', function (): void {
    CarbonImmutable::setTestNow('2026-11-08 10:00');
    $range = new DashboardRange('7d');
    $previous = $range->previous();

    expect($range->from->toDateString())->toBe('2026-11-02')
        ->and($range->to->toDateTimeString())->toBe('2026-11-08 23:59:59')
        ->and($previous->from->toDateString())->toBe('2026-10-26')
        ->and($previous->to->toDateString())->toBe('2026-11-01')
        ->and(DashboardRange::change(118, 100))->toBe(18.0)
        ->and(DashboardRange::change(95, 100))->toBe(-5.0)
        ->and(DashboardRange::change(5, 0))->toBeNull();
});

it('lets only a stat widget be a highlight', function (): void {
    expect(widgetOf(WidgetType::Stat, new StatWidgetData(1), highlight: true)->meta()->highlight)->toBeTrue()
        ->and(fn () => widgetOf(WidgetType::List, new ListWidgetData([]), highlight: true)->meta())
        ->toThrow(LogicException::class, 'only a stat widget can be a highlight');
});

it('rebuilds nested rows from the cache', function (): void {
    $rows = array_map(fn (int $i): ListRowData => new ListRowData(key: (string) $i, title: "INV-{$i}", value: 'Rp 1.000', tone: 'success'), range(1, 8));
    $widget = widgetOf(WidgetType::List, new ListWidgetData($rows));
    $user = makeUser();

    $widget->resolve($user, new DashboardRange);
    $cached = $widget->resolve($user, new DashboardRange);

    $rows = $cached instanceof ListWidgetData ? $cached->rows : [];

    expect($cached)->toBeInstanceOf(ListWidgetData::class)
        ->and($rows)->toHaveCount(ListWidgetData::MAX_ROWS)
        ->and($rows[0] ?? null)->toEqual(new ListRowData(key: '1', title: 'INV-1', value: 'Rp 1.000', tone: 'success'));
});
