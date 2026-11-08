import * as React from 'react';
import { cn } from '../../lib/utils';

/** shadcn-style Switch (`role="switch"`), in the house style; `SwitchField` wraps it with a label. */
function Switch({
    checked,
    onCheckedChange,
    className,
    ...props
}: Omit<React.ComponentProps<'button'>, 'onChange'> & {
    checked: boolean;
    onCheckedChange: (checked: boolean) => void;
}) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            data-slot="switch"
            onClick={() => onCheckedChange(!checked)}
            className={cn(
                'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full border border-transparent transition-colors duration-200 disabled:opacity-50',
                checked ? 'bg-primary' : 'bg-input',
                className,
            )}
            {...props}
        >
            <span
                aria-hidden="true"
                className={cn(
                    'block size-4 rounded-full bg-background transition-transform duration-200',
                    checked ? 'translate-x-4' : 'translate-x-0.5',
                )}
            />
        </button>
    );
}

export { Switch };
