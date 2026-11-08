import type { LucideIcon } from 'lucide-react';
import type { KeyboardEvent, ReactNode } from 'react';
import { useId } from 'react';
import { cn } from '../../lib/utils';

type Tab = {
    key: string;
    label: string;
    content: ReactNode;
    icon?: LucideIcon;
};

type Props = {
    label: string;
    tabs: Tab[];
    value: string;
    onChange: (key: string) => void;
    /** `segmented`: the full-width tab bar of settings pages (demo "Account Setting"). */
    variant?: 'underline' | 'segmented';
};

/**
 * Show-page sections (ui-design §3): a tab row over a 1px rule, the active tab
 * underlined; or (`segmented`) a muted bar whose active tab is a raised
 * card. Arrow keys, Home and End move between tabs.
 */
export function Tabs({
    label,
    tabs,
    value,
    onChange,
    variant = 'underline',
}: Props) {
    const segmented = variant === 'segmented';
    const id = useId();
    const active = tabs.find((tab) => tab.key === value) ?? tabs[0];

    const onKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
        const index = tabs.findIndex((tab) => tab.key === active.key);
        const next = {
            ArrowRight: index + 1,
            ArrowLeft: index - 1,
            Home: 0,
            End: tabs.length - 1,
        }[event.key];

        if (next === undefined) {
            return;
        }

        event.preventDefault();
        const tab = tabs[(next + tabs.length) % tabs.length];
        onChange(tab.key);
        document.getElementById(`${id}-tab-${tab.key}`)?.focus();
    };

    return (
        <div className={cn('flex flex-col', segmented ? 'gap-6' : 'gap-4')}>
            <div
                role="tablist"
                aria-label={label}
                onKeyDown={onKeyDown}
                className={cn(
                    'flex overflow-x-auto',
                    segmented
                        ? 'gap-1 rounded-xl bg-muted p-1'
                        : 'gap-1 border-b border-border',
                )}
            >
                {tabs.map((tab) => {
                    const selected = tab.key === active.key;

                    return (
                        <button
                            key={tab.key}
                            id={`${id}-tab-${tab.key}`}
                            type="button"
                            role="tab"
                            aria-selected={selected}
                            aria-controls={`${id}-panel-${tab.key}`}
                            tabIndex={selected ? 0 : -1}
                            onClick={() => onChange(tab.key)}
                            className={cn(
                                'inline-flex items-center justify-center gap-2 text-body whitespace-nowrap tabular-nums transition-colors duration-150',
                                segmented
                                    ? cn(
                                          'h-10 flex-1 rounded-lg px-4',
                                          selected
                                              ? 'bg-card font-medium text-foreground shadow-xs'
                                              : 'text-muted-foreground hover:text-foreground',
                                      )
                                    : cn(
                                          '-mb-px h-10 border-b-2 px-3',
                                          selected
                                              ? 'border-primary font-semibold text-foreground'
                                              : 'border-transparent font-medium text-muted-foreground hover:text-foreground',
                                      ),
                            )}
                        >
                            {tab.icon && (
                                <tab.icon
                                    aria-hidden="true"
                                    className="size-4"
                                />
                            )}
                            {tab.label}
                        </button>
                    );
                })}
            </div>
            <div
                role="tabpanel"
                id={`${id}-panel-${active.key}`}
                aria-labelledby={`${id}-tab-${active.key}`}
            >
                {active.content}
            </div>
        </div>
    );
}
