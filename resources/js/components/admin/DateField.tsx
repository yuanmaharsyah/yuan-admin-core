import { CalendarDays } from 'lucide-react';
import { useState } from 'react';
import { formatLocalDate, parseLocalDate } from '../../lib/format';
import { useT } from '../../lib/i18n';
import { Button } from '../ui/button';
import { Calendar } from '../ui/calendar';
import { Input } from '../ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '../ui/popover';
import { controlProps, FormField } from './FormField';
import type { FieldProps } from './FormField';

type Props = FieldProps<string | null> & {
    /** Adds a time input; the value becomes `YYYY-MM-DD HH:mm`. */
    withTime?: boolean;
};

/**
 * A date (D20): shows "20 Okt 2026", keeps ISO `YYYY-MM-DD` (app timezone, no
 * offset), takes a typed `dd/mm/yyyy`, or a pick from the calendar.
 */
export function DateField({
    name,
    label,
    value,
    onChange,
    error,
    required,
    help,
    disabled,
    withTime = false,
}: Props) {
    const t = useT();
    const [open, setOpen] = useState(false);
    const date = value?.slice(0, 10) ?? null;
    const time = value?.slice(11, 16) || '00:00';
    const show = (iso: string | null) => (iso ? formatLocalDate(iso) : '');
    const [text, setText] = useState(() => show(date));
    const set = (nextDate: string | null, nextTime = time) => {
        setText(show(nextDate));
        onChange(
            nextDate === null
                ? null
                : withTime
                  ? `${nextDate} ${nextTime}`
                  : nextDate,
        );
    };

    return (
        <FormField
            name={name}
            label={label}
            required={required}
            help={help}
            error={error}
        >
            <div className="flex gap-2">
                <div className="relative flex-1">
                    <Input
                        {...controlProps({ name, error, required, disabled })}
                        value={text}
                        placeholder={t('form.date_placeholder')}
                        autoComplete="off"
                        className="pr-10"
                        onChange={(event) => setText(event.target.value)}
                        onBlur={() => {
                            if (text.trim() === '') {
                                set(null);
                            } else if (text !== show(date)) {
                                set(parseLocalDate(text) ?? date);
                            }
                        }}
                    />
                    <Popover open={open} onOpenChange={setOpen}>
                        <PopoverTrigger asChild>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                disabled={disabled}
                                aria-label={`${t('form.open_calendar')}: ${label}`}
                                className="absolute top-1/2 right-0.5 size-control-sm -translate-y-1/2"
                            >
                                <CalendarDays />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent align="end" className="w-auto">
                            <Calendar
                                mode="single"
                                selected={
                                    date
                                        ? new Date(`${date}T00:00:00`)
                                        : undefined
                                }
                                defaultMonth={
                                    date
                                        ? new Date(`${date}T00:00:00`)
                                        : undefined
                                }
                                onSelect={(day) => {
                                    set(day ? toIso(day) : null);
                                    setOpen(false);
                                }}
                            />
                        </PopoverContent>
                    </Popover>
                </div>
                {withTime && (
                    <Input
                        type="time"
                        aria-label={`${label}: ${t('form.time')}`}
                        disabled={disabled || date === null}
                        value={date === null ? '' : time}
                        onChange={(event) =>
                            set(date, event.target.value || '00:00')
                        }
                        className="w-28 tabular-nums"
                    />
                )}
            </div>
        </FormField>
    );
}

/** The calendar's local day as `YYYY-MM-DD`. */
function toIso(day: Date): string {
    return [
        day.getFullYear(),
        String(day.getMonth() + 1).padStart(2, '0'),
        String(day.getDate()).padStart(2, '0'),
    ].join('-');
}
