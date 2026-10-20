import { Command } from 'cmdk';
import { useRef, useState } from 'react';
import { useT } from '../../lib/i18n';
import { Popover, PopoverContent, PopoverTrigger } from '../ui/popover';
import { ComboboxButton, ComboboxItem } from './ComboboxField';
import { controlProps, FormField } from './FormField';
import type { FieldProps } from './FormField';

type Props = FieldProps<number | null> & {
    /** The target module's Wayfinder `lookup` route; answers `LookupData` (20 results). */
    lookupRoute: (options: { query: { search: string } }) => { url: string };
    /** Label of the current value, from the edit Data. */
    valueLabel?: string | null;
    placeholder?: string;
};

/** A record of another module, searched on its lookup route as you type (300ms). */
export function RelationSelect({
    name,
    label,
    value,
    onChange,
    error,
    required,
    help,
    disabled,
    lookupRoute,
    valueLabel,
    placeholder,
}: Props) {
    const t = useT();
    const [open, setOpen] = useState(false);
    const [selectedLabel, setSelectedLabel] = useState(valueLabel ?? null);
    const [items, setItems] = useState<Yuan.AdminCore.Data.OptionData[] | null>(
        null,
    );
    const [failed, setFailed] = useState(false);
    const timer = useRef<number | undefined>(undefined);
    const request = useRef<AbortController | null>(null);

    const search = (term: string) => {
        window.clearTimeout(timer.current);
        timer.current = window.setTimeout(() => {
            request.current?.abort();
            request.current = new AbortController();
            setFailed(false);
            fetch(lookupRoute({ query: { search: term } }).url, {
                headers: { Accept: 'application/json' },
                signal: request.current.signal,
            })
                .then((response) =>
                    response.ok
                        ? (response.json() as Promise<Yuan.AdminCore.Data.LookupData>)
                        : Promise.reject(response),
                )
                .then((data) => setItems(data.items))
                .catch((reason: unknown) => {
                    if (!(
                        reason instanceof DOMException &&
                        reason.name === 'AbortError'
                    )) {
                        setFailed(true);
                    }
                });
        }, 300);
    };

    return (
        <FormField
            name={name}
            label={label}
            required={required}
            help={help}
            error={error}
        >
            <Popover
                open={open}
                onOpenChange={(next) => {
                    setOpen(next);

                    if (next && items === null) {
                        search('');
                    }
                }}
            >
                <PopoverTrigger asChild>
                    <ComboboxButton
                        {...controlProps({ name, error, required, disabled })}
                        open={open}
                        label={value === null ? null : selectedLabel}
                        placeholder={placeholder ?? t('form.choose')}
                    />
                </PopoverTrigger>
                <PopoverContent className="w-80 max-w-[calc(100vw-2rem)] p-0">
                    <Command shouldFilter={false} loop>
                        <Command.Input
                            placeholder={t('form.search')}
                            onValueChange={search}
                            className="h-control w-full border-b border-border bg-transparent px-3 text-body outline-none"
                        />
                        <Command.List className="max-h-64 overflow-y-auto p-1">
                            {failed ? (
                                <div
                                    role="alert"
                                    className="px-3 py-6 text-center text-table text-destructive"
                                >
                                    {t('form.lookup_failed')}
                                </div>
                            ) : items === null ? (
                                <Command.Loading className="px-3 py-6 text-center text-table text-muted-foreground">
                                    {t('form.searching')}
                                </Command.Loading>
                            ) : (
                                <Command.Empty className="px-3 py-6 text-center text-table text-muted-foreground">
                                    {t('form.no_results')}
                                </Command.Empty>
                            )}
                            {items?.map((item) => (
                                <ComboboxItem
                                    key={item.value}
                                    value={item.value}
                                    label={item.label}
                                    selected={Number(item.value) === value}
                                    onSelect={() => {
                                        onChange(Number(item.value));
                                        setSelectedLabel(item.label);
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
