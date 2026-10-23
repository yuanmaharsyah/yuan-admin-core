import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '../../lib/utils';
import { Spinner } from './spinner';

const buttonVariants = cva(
    "inline-flex items-center justify-center gap-2 rounded text-body font-medium whitespace-nowrap transition-[color,box-shadow] disabled:pointer-events-none disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
    {
        variants: {
            variant: {
                default:
                    'bg-primary text-primary-foreground hover:bg-primary/90',
                destructive:
                    'bg-destructive text-destructive-foreground hover:bg-destructive/90',
                outline:
                    'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
                secondary:
                    'bg-secondary text-secondary-foreground hover:bg-secondary/80',
                ghost: 'hover:bg-accent hover:text-accent-foreground',
                link: 'text-primary underline-offset-4 hover:underline',
            },
            size: {
                default: 'h-control px-4 py-2 has-[>svg]:px-3',
                sm: 'h-control-sm rounded px-3 has-[>svg]:px-2.5',
                auth: 'h-control-auth rounded px-6 has-[>svg]:px-4',
                compact: 'h-control-compact rounded px-2.5 has-[>svg]:px-2',
                icon: 'size-control',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    },
);

/**
 * `loading` (frontend-behavior §2): a spinner before the label, disabled and
 * `aria-busy` while the server works, so the action can't be sent twice.
 */
function Button({
    className,
    variant,
    size,
    asChild = false,
    loading = false,
    disabled,
    children,
    ...props
}: React.ComponentProps<'button'> &
    VariantProps<typeof buttonVariants> & {
        asChild?: boolean;
        loading?: boolean;
    }) {
    if (asChild) {
        return (
            <Slot
                data-slot="button"
                className={cn(buttonVariants({ variant, size, className }))}
                {...props}
            >
                {children}
            </Slot>
        );
    }

    return (
        <button
            data-slot="button"
            className={cn(buttonVariants({ variant, size, className }))}
            disabled={disabled || loading}
            aria-busy={loading || undefined}
            {...props}
        >
            {loading && <Spinner aria-hidden="true" role={undefined} />}
            {children}
        </button>
    );
}

export { Button, buttonVariants };
