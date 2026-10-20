import { cn } from '../../lib/utils';
import { controlProps, fieldMessageId } from './FormField';
import type { FieldProps } from './FormField';
import { Label } from '../ui/label';

/** An on/off setting: a switch button with its label beside it. */
export function SwitchField({
    name,
    label,
    value,
    onChange,
    error,
    help,
    disabled,
}: FieldProps<boolean>) {
    return (
        <div className="flex flex-col gap-1.5">
            <div className="flex items-center gap-3">
                <button
                    type="button"
                    role="switch"
                    aria-checked={value}
                    {...controlProps({ name, error, disabled })}
                    onClick={() => onChange(!value)}
                    className={cn(
                        'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full border border-transparent transition-colors disabled:opacity-50',
                        value ? 'bg-primary' : 'bg-input',
                    )}
                >
                    <span
                        aria-hidden="true"
                        className={cn(
                            'block size-4 rounded-full bg-background transition-transform',
                            value ? 'translate-x-4' : 'translate-x-0.5',
                        )}
                    />
                </button>
                <Label htmlFor={name}>{label}</Label>
            </div>
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
