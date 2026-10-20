import type { KeyboardEvent, ReactNode } from 'react';
import { useId } from 'react';
import { cn } from '../../lib/utils';

type Tab = { key: string; label: string; content: ReactNode };

type Props = {
    label: string;
    tabs: Tab[];
    value: string;
    onChange: (key: string) => void;
};

/**
 * Show-page sections (ui-design §3): a tab row over a 1px rule, the active tab
 * underlined in ink. Arrow keys, Home and End move between tabs.
 */
export function Tabs({ label, tabs, value, onChange }: Props) {
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
        <div className="flex flex-col gap-4">
            <div
                role="tablist"
                aria-label={label}
                onKeyDown={onKeyDown}
                className="flex gap-1 overflow-x-auto border-b border-border"
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
                                '-mb-px h-10 border-b-2 px-3 text-body whitespace-nowrap tabular-nums',
                                selected
                                    ? 'border-rule font-semibold text-foreground'
                                    : 'border-transparent font-medium text-muted-foreground hover:text-foreground',
                            )}
                        >
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
