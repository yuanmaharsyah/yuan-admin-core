import type { ReactNode } from 'react';
import { Label } from '../ui/label';

type Props = {
    name: string;
    label: string;
    required?: boolean;
    help?: string;
    error?: string;
    children: ReactNode;
};

/**
 * Label, control, help and error (frontend-behavior §9). The control uses
 * `id={name}` and `aria-describedby={fieldMessageId(name)}`.
 */
export function FormField({
    name,
    label,
    required = false,
    help,
    error,
    children,
}: Props) {
    return (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={name} className="flex gap-0.5">
                {label}
                {required && (
                    <span aria-hidden="true" className="text-destructive">
                        *
                    </span>
                )}
            </Label>
            {children}
            <div id={fieldMessageId(name)} className="flex flex-col gap-0.5">
                {help && (
                    <span className="text-xs text-muted-foreground">
                        {help}
                    </span>
                )}
                {error && (
                    <span role="alert" className="text-table text-destructive">
                        {error}
                    </span>
                )}
            </div>
        </div>
    );
}

export function fieldMessageId(name: string): string {
    return `${name}-message`;
}
