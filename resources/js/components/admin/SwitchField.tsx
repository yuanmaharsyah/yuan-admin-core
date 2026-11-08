import { controlProps, fieldMessageId } from './FormField';
import type { FieldProps } from './FormField';
import { Label } from '../ui/label';
import { Switch } from '../ui/switch';

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
                <Switch
                    checked={value}
                    onCheckedChange={onChange}
                    {...controlProps({ name, error, disabled })}
                />
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
