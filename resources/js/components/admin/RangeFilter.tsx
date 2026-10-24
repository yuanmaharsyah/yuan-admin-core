import { differenceInCalendarDays, parseISO } from 'date-fns';
import { Check, ChevronDown } from 'lucide-react';
import { useState } from 'react';
import { formatLocalDate } from '../../lib/format';
import { useT } from '../../lib/i18n';
import { Button } from '../ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '../ui/popover';
import { DateField } from './DateField';

export type RangeValue = { range: string; from: string; to: string };

type Props = {
    /** The preset ranges, e.g. 7d / 30d / 90d; `custom` is added for picked dates. */
    presets: { key: string; label: string }[];
    value: RangeValue;
    /** The longest custom range; longer ones can't be applied (the server refuses them too). */
    maxDays: number;
    onChange: (value: RangeValue) => void;
};

/**
 * A date range for summaries (frontend-behavior §8.3): a preset, or two
 * picked dates up to `maxDays` days apart.
 */
export function RangeFilter({ presets, value, maxDays, onChange }: Props) {
    const t = useT();
    const [open, setOpen] = useState(false);
    const [from, setFrom] = useState<string | null>(value.from);
    const [to, setTo] = useState<string | null>(value.to);
    const days =
        from && to
            ? differenceInCalendarDays(parseISO(to), parseISO(from)) + 1
            : null;
    const error =
        days === null
            ? undefined
            : days < 1
              ? t('filter.range_order')
              : days > maxDays
                ? t('filter.range_too_long', { days: maxDays })
                : undefined;
    const preset = presets.find((item) => item.key === value.range);
    const text =
        preset?.label ??
        `${formatLocalDate(value.from)} – ${formatLocalDate(value.to)}`;

    return (
        <Popover
            open={open}
            onOpenChange={(next) => {
                setOpen(next);
                setFrom(value.from);
                setTo(value.to);
            }}
        >
            <PopoverTrigger
                aria-label={`${t('filter.range')}: ${text}`}
                className="flex h-control items-center gap-2 rounded border border-border bg-card px-2.5 text-table font-medium whitespace-nowrap tabular-nums"
            >
                {text}
                <ChevronDown
                    aria-hidden="true"
                    className="size-3.5 text-muted-foreground"
                />
            </PopoverTrigger>
            <PopoverContent
                align="start"
                className="flex w-80 flex-col gap-3 p-3"
            >
                <div className="flex flex-col">
                    {presets.map((item) => (
                        <button
                            key={item.key}
                            type="button"
                            className="flex h-8 items-center gap-2 rounded px-2 text-left text-table hover:bg-accent"
                            onClick={() => {
                                setOpen(false);
                                onChange({ ...value, range: item.key });
                            }}
                        >
                            <span
                                aria-hidden="true"
                                className="flex w-4 justify-center"
                            >
                                {item.key === value.range && (
                                    <Check className="size-3.5" />
                                )}
                            </span>
                            {item.label}
                        </button>
                    ))}
                </div>
                <div className="flex flex-col gap-3 border-t border-border pt-3">
                    <DateField
                        name="from"
                        label={t('filter.range_from')}
                        value={from}
                        onChange={setFrom}
                    />
                    <DateField
                        name="to"
                        label={t('filter.range_to')}
                        value={to}
                        onChange={setTo}
                        error={error}
                    />
                    <Button
                        size="sm"
                        disabled={!from || !to || error !== undefined}
                        onClick={() => {
                            if (from && to) {
                                setOpen(false);
                                onChange({ range: 'custom', from, to });
                            }
                        }}
                    >
                        {t('filter.range_apply')}
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    );
}
