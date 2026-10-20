import { id } from 'date-fns/locale';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import * as React from 'react';
import { DayPicker } from 'react-day-picker';

import { cn } from '../../lib/utils';
import { buttonVariants } from './button';

/** shadcn Calendar on react-day-picker 9, Indonesian locale, theme tokens only. */
function Calendar({
    className,
    classNames,
    ...props
}: React.ComponentProps<typeof DayPicker>) {
    return (
        <DayPicker
            locale={id}
            showOutsideDays
            className={cn('p-2', className)}
            classNames={{
                months: 'relative flex flex-col',
                month: 'flex flex-col gap-3',
                month_caption:
                    'flex h-control-sm items-center justify-center text-body font-semibold',
                nav: 'absolute inset-x-0 top-0 flex justify-between',
                button_previous: cn(
                    buttonVariants({ variant: 'ghost', size: 'icon' }),
                    'size-control-sm',
                ),
                button_next: cn(
                    buttonVariants({ variant: 'ghost', size: 'icon' }),
                    'size-control-sm',
                ),
                weekdays: 'flex',
                weekday:
                    'w-9 text-center text-label font-medium text-muted-foreground',
                week: 'mt-1 flex',
                day: 'size-9 p-0 text-center text-table',
                day_button:
                    'size-9 rounded tabular-nums hover:bg-accent aria-selected:bg-primary aria-selected:text-primary-foreground',
                today: 'font-bold underline underline-offset-4',
                outside: 'text-muted-foreground opacity-60',
                disabled: 'opacity-40',
                hidden: 'invisible',
                ...classNames,
            }}
            components={{
                Chevron: ({ orientation }) =>
                    orientation === 'left' ? (
                        <ChevronLeft className="size-4" />
                    ) : (
                        <ChevronRight className="size-4" />
                    ),
            }}
            {...props}
        />
    );
}

export { Calendar };
