import type { ReactNode } from 'react';

type Props = {
    items: { label: string; value: ReactNode }[];
};

/** Label / value pairs in two columns (one on narrow screens), each over a 1px rule. */
export function DetailGrid({ items }: Props) {
    return (
        <dl className="m-0 grid grid-cols-1 gap-x-8 gap-y-4 md:grid-cols-2">
            {items.map((item) => (
                <div
                    key={item.label}
                    className="flex min-w-0 flex-col gap-1 border-b border-border pb-3"
                >
                    <dt className="text-label text-muted-foreground uppercase">
                        {item.label}
                    </dt>
                    <dd className="m-0 text-body font-medium break-words tabular-nums">
                        {item.value ?? '—'}
                    </dd>
                </div>
            ))}
        </dl>
    );
}
