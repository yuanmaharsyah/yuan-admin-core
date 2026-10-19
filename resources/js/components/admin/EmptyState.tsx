import type { LucideIcon } from 'lucide-react';
import { Inbox } from 'lucide-react';
import type { ReactNode } from 'react';

export type EmptyStateProps = {
    title: string;
    description?: string;
    action?: ReactNode;
    icon?: LucideIcon;
};

/** Centered icon tile, title, text and one optional action (lists, tabs, Beranda). */
export function EmptyState({
    title,
    description,
    action,
    icon: Icon = Inbox,
}: EmptyStateProps) {
    return (
        <div className="flex flex-col items-center gap-2.5 px-6 py-18 text-center">
            <div
                aria-hidden="true"
                className="flex size-12 items-center justify-center rounded bg-muted text-muted-foreground"
            >
                <Icon className="size-5" />
            </div>
            <h2 className="mt-1.5 mb-0 text-card-title">{title}</h2>
            {description && (
                <p className="m-0 max-w-sm text-pretty text-muted-foreground">
                    {description}
                </p>
            )}
            {action && <div className="mt-2">{action}</div>}
        </div>
    );
}
