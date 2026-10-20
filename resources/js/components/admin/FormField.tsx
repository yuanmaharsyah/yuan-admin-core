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

/** The props every form control shares (frontend-behavior §6, §9). */
export type FieldProps<V> = {
    name: string;
    label: string;
    value: V;
    onChange: (value: V) => void;
    error?: string;
    required?: boolean;
    help?: string;
    disabled?: boolean;
};

/** Wires a control to its FormField: id, invalid state, message and required. */
export function controlProps(field: {
    name: string;
    error?: string;
    required?: boolean;
    disabled?: boolean;
}) {
    return {
        id: field.name,
        name: field.name,
        'aria-invalid': field.error ? true : undefined,
        'aria-describedby': fieldMessageId(field.name),
        'aria-required': field.required || undefined,
        disabled: field.disabled,
    };
}
