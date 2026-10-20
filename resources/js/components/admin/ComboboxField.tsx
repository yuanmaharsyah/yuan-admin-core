import { Command } from 'cmdk';
import { Check, ChevronDown } from 'lucide-react';
import { useState } from 'react';
import type { ComponentProps } from 'react';
import { useT } from '../../lib/i18n';
import { cn } from '../../lib/utils';
import { Popover, PopoverContent, PopoverTrigger } from '../ui/popover';
import { controlProps, FormField } from './FormField';
import type { FieldProps } from './FormField';

type Props = FieldProps<string | null> & {
    options: Yuan.AdminCore.Data.OptionData[];
    placeholder?: string;
};

/**
 * A fixed list with search: a popover with a filtered list. The list lives
 * outside the form, so Enter in it picks an option and never submits.
 */
export function ComboboxField({
    name,
    label,
    value,
    onChange,
    error,
    required,
    help,
    disabled,
    options,
    placeholder,
}: Props) {
    const t = useT();
    const [open, setOpen] = useState(false);
    const selected = options.find((option) => option.value === value);

    return (
        <FormField
            name={name}
            label={label}
            required={required}
            help={help}
            error={error}
        >
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <ComboboxButton
                        {...controlProps({ name, error, required, disabled })}
                        open={open}
                        label={selected?.label}
                        placeholder={placeholder ?? t('form.choose')}
                    />
                </PopoverTrigger>
                <PopoverContent className="w-80 max-w-[calc(100vw-2rem)] p-0">
                    <Command loop>
                        <Command.Input
                            placeholder={t('form.search')}
                            className="h-control w-full border-b border-border bg-transparent px-3 text-body outline-none"
                        />
                        <Command.List className="max-h-64 overflow-y-auto p-1">
                            <Command.Empty className="px-3 py-6 text-center text-table text-muted-foreground">
                                {t('form.no_results')}
                            </Command.Empty>
                            {options.map((option) => (
                                <ComboboxItem
                                    key={option.value}
                                    value={option.label}
                                    label={option.label}
                                    selected={option.value === value}
                                    onSelect={() => {
                                        onChange(option.value);
                                        setOpen(false);
                                    }}
                                />
                            ))}
                        </Command.List>
                    </Command>
                </PopoverContent>
            </Popover>
        </FormField>
    );
}

export function ComboboxButton({
    open,
    label,
    placeholder,
    ...button
}: ComponentProps<'button'> & {
    open: boolean;
    label?: string | null;
    placeholder: string;
}) {
    return (
        <button
            type="button"
            role="combobox"
            aria-expanded={open}
            {...button}
            className="flex h-control w-full items-center gap-2 rounded border border-input bg-transparent px-3 text-left text-body disabled:opacity-50 aria-invalid:border-destructive"
        >
            <span
                className={cn(
                    'min-w-0 flex-1 truncate',
                    !label && 'text-muted-foreground',
                )}
            >
                {label ?? placeholder}
            </span>
            <ChevronDown
                aria-hidden="true"
                className="size-4 shrink-0 text-muted-foreground"
            />
        </button>
    );
}

export function ComboboxItem({
    value,
    label,
    selected,
    onSelect,
}: {
    value: string;
    label: string;
    selected: boolean;
    onSelect: () => void;
}) {
    return (
        <Command.Item
            value={value}
            onSelect={onSelect}
            className="flex h-control-sm cursor-default items-center gap-2 rounded px-2 text-body data-[selected=true]:bg-accent"
        >
            <Check
                aria-hidden="true"
                className={cn('size-4', selected ? 'opacity-100' : 'opacity-0')}
            />
            {label}
        </Command.Item>
    );
}
