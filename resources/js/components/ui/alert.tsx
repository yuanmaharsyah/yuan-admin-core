import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '../../lib/utils';

// Soft status style (ui-design §1.1): status colour at 12% behind ink text;
// the icon and the title take the status colour.
const alertVariants = cva(
    'flex w-full gap-2.5 rounded p-3 text-table leading-normal [&>svg]:mt-0.5 [&>svg]:size-4 [&>svg]:shrink-0',
    {
        variants: {
            variant: {
                default: 'bg-muted',
                destructive:
                    'bg-destructive/12 [&_[data-slot=alert-title]]:text-destructive [&>svg]:text-destructive',
                warning:
                    'bg-warning/12 [&_[data-slot=alert-title]]:text-warning [&>svg]:text-warning',
                info: 'bg-info/12 [&_[data-slot=alert-title]]:text-info [&>svg]:text-info',
                success:
                    'bg-success/12 [&_[data-slot=alert-title]]:text-success [&>svg]:text-success',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    },
);

/** Errors and warnings are announced (`role="alert"`), the rest is `status`. */
function Alert({
    className,
    variant,
    ...props
}: React.ComponentProps<'div'> & VariantProps<typeof alertVariants>) {
    return (
        <div
            data-slot="alert"
            role={
                variant === 'destructive' || variant === 'warning'
                    ? 'alert'
                    : 'status'
            }
            className={cn(alertVariants({ variant }), className)}
            {...props}
        />
    );
}

function AlertTitle({ className, ...props }: React.ComponentProps<'strong'>) {
    return (
        <strong
            data-slot="alert-title"
            className={cn('block font-semibold', className)}
            {...props}
        />
    );
}

function AlertDescription({
    className,
    ...props
}: React.ComponentProps<'div'>) {
    return (
        <div
            data-slot="alert-description"
            className={cn('text-foreground', className)}
            {...props}
        />
    );
}

export { Alert, AlertTitle, AlertDescription };
