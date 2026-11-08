import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { cn } from '../../lib/utils';
import { Skeleton } from '../ui/skeleton';
import { CardHeading } from './CardHeading';
import { ChangePill } from './ChangePill';
import { Icon } from './Icon';

type Row = Yuan.AdminCore.Data.Widgets.ListRowData;

const TONES: Record<Row['tone'], string> = {
    neutral: 'text-foreground',
    success: 'text-success',
    destructive: 'text-destructive',
};

/** Rows of icon, title, subtitle and a right-hand value, with an optional change pill and share bar (demo "Recent Transactions", "Sales by Countries"). */
export function ListCard({
    title,
    rows,
    empty,
    action,
    loading = false,
}: {
    title: string;
    rows: Row[];
    empty: string;
    action?: ReactNode;
    loading?: boolean;
}) {
    return (
        <section
            aria-label={title}
            aria-busy={loading || undefined}
            className="flex min-w-0 flex-col gap-4 rounded-xl border border-border bg-card p-6 text-card-foreground"
        >
            <CardHeading title={title} action={action} />
            {loading ? (
                <div className="flex flex-col gap-4">
                    {[0, 1, 2, 3].map((row) => (
                        <div key={row} className="flex items-center gap-3">
                            <Skeleton className="size-10 rounded-lg" />
                            <div className="flex flex-1 flex-col gap-1.5">
                                <Skeleton className="h-4 w-32" />
                                <Skeleton className="h-3 w-20" />
                            </div>
                            <Skeleton className="h-4 w-16" />
                        </div>
                    ))}
                </div>
            ) : rows.length === 0 ? (
                <p className="m-0 text-table text-muted-foreground">{empty}</p>
            ) : (
                <ul className="m-0 flex list-none flex-col gap-1 p-0">
                    {rows.map((row) => {
                        const content = (
                            <>
                                <span
                                    aria-hidden="true"
                                    className="flex size-10 shrink-0 items-center justify-center rounded-lg border border-border"
                                >
                                    <Icon
                                        name={row.icon}
                                        className="size-4.5"
                                    />
                                </span>
                                <span className="flex min-w-0 flex-1 flex-col">
                                    <span className="truncate font-medium">
                                        {row.title}
                                    </span>
                                    {row.subtitle && (
                                        <span className="truncate text-table text-muted-foreground">
                                            {row.subtitle}
                                        </span>
                                    )}
                                    {row.progress !== null && (
                                        <svg
                                            aria-hidden="true"
                                            viewBox="0 0 1 1"
                                            preserveAspectRatio="none"
                                            className="mt-2 h-1.5 w-full max-w-48 overflow-hidden rounded-full"
                                        >
                                            <rect
                                                width="1"
                                                height="1"
                                                className="fill-muted"
                                            />
                                            <rect
                                                width={Math.min(
                                                    1,
                                                    Math.max(0, row.progress),
                                                )}
                                                height="1"
                                                className="fill-primary"
                                            />
                                        </svg>
                                    )}
                                </span>
                                {row.value !== '' && (
                                    <span
                                        className={cn(
                                            'shrink-0 font-semibold tabular-nums',
                                            TONES[row.tone],
                                        )}
                                    >
                                        {row.value}
                                    </span>
                                )}
                                {row.change !== null && (
                                    <ChangePill change={row.change} />
                                )}
                            </>
                        );
                        const rowClass =
                            'flex items-center gap-3 rounded-lg py-2 no-underline';

                        return (
                            <li key={row.key}>
                                {row.href ? (
                                    <Link
                                        href={row.href}
                                        className={cn(
                                            rowClass,
                                            '-mx-2 px-2 text-foreground transition-colors duration-150 hover:bg-accent',
                                        )}
                                    >
                                        {content}
                                    </Link>
                                ) : (
                                    <div className={rowClass}>{content}</div>
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}
        </section>
    );
}
